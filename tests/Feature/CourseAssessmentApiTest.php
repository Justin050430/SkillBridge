<?php

/**
 * @author OOI JIAN XIN
 *
 * Verifies the Assessment & Progress module's exposed course-level web
 * service:
 *   GET /api/assessments/course/{course}/stats (IFA-compliant)
 * Consumed by the Course Management module.
 */

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CourseAssessmentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(['role_name' => $roleName]);

        return User::create([
            'role_id' => $role->id,
            'first_name' => 'Test',
            'last_name' => $roleName,
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
    }

    protected function makeCourse(User $mentor): Course
    {
        $category = Category::create(['category_name' => 'Category '.uniqid()]);

        return Course::create([
            'mentor_id' => $mentor->id,
            'category_id' => $category->id,
            'title' => 'Course '.uniqid(),
            'slug' => 'course-'.uniqid(),
            'description' => 'Test course',
            'status' => 'Published',
        ]);
    }

    protected function makeLesson(Course $course): Lesson
    {
        return Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson '.uniqid(),
            'content' => 'Content',
            'order_number' => 1,
            'status' => 'Published',
        ]);
    }

    protected function makeAssessment(Lesson $lesson): Assessment
    {
        return Assessment::create([
            'lesson_id' => $lesson->id,
            'title' => 'Assessment '.uniqid(),
            'passing_score' => 50,
            'status' => 'Published',
        ]);
    }

    protected function makeAttempt(User $student, Assessment $assessment, float $percentage, bool $passed): void
    {
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $assessment->lesson->course_id,
            'status' => 'Active',
            'enrolled_at' => now(),
        ]);

        AssessmentAttempt::create([
            'enrollment_id' => $enrollment->id,
            'assessment_id' => $assessment->id,
            'attempt_number' => 1,
            'score' => 1,
            'percentage' => $percentage,
            'is_passed' => $passed,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);
    }

    public function test_course_stats_returns_aggregates_with_ifa_fields(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $course = $this->makeCourse($mentor);
        $assessment = $this->makeAssessment($this->makeLesson($course));
        $this->makeAttempt($student, $assessment, 80.0, true);
        $this->makeAttempt($student, $assessment, 40.0, false);

        $response = $this->actingAs($admin)
            ->getJson('/api/assessments/course/'.$course->id.'/stats?timestamp=2026-01-01T00:00:00Z');

        $response->assertOk();
        $response->assertJson([
            'status' => 'S',
            'requestId' => true,
            'timestamp' => true,
        ]);
        $response->assertJsonPath('data.course_id', $course->id);
        $response->assertJsonPath('data.course_title', $course->title);
        $response->assertJsonPath('data.assessment_count', 1);
        $response->assertJsonPath('data.attempt_count', 2);
        $response->assertJsonPath('data.pass_rate', 50);
        $response->assertJsonPath('data.average_score', 60);
    }

    public function test_course_stats_accepts_request_id_instead_of_timestamp(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourse($mentor);

        $this->actingAs($admin)
            ->getJson('/api/assessments/course/'.$course->id.'/stats?requestId=req-course-1')
            ->assertOk()
            ->assertJsonPath('status', 'S')
            ->assertJsonPath('requestId', 'req-course-1');
    }

    public function test_course_stats_requires_timestamp_or_request_id(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourse($mentor);

        $response = $this->actingAs($admin)
            ->getJson('/api/assessments/course/'.$course->id.'/stats');

        $response->assertStatus(422);
        $response->assertJsonPath('status', 'F');
        $response->assertJsonPath('message', 'A timestamp or requestId field is required.');
    }

    public function test_course_stats_requires_authentication(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourse($mentor);

        $this->getJson('/api/assessments/course/'.$course->id.'/stats?timestamp=2026-01-01T00:00:00Z')
            ->assertUnauthorized();
    }

    public function test_course_stats_blocks_students_and_other_mentors(): void
    {
        $mentorA = $this->makeUser('Mentor', 'mentorA@test.com');
        $mentorB = $this->makeUser('Mentor', 'mentorB@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $courseA = $this->makeCourse($mentorA);

        // A student must not access course analytics.
        $this->actingAs($student)
            ->getJson('/api/assessments/course/'.$courseA->id.'/stats?timestamp=2026-01-01T00:00:00Z')
            ->assertForbidden();

        // A mentor who does not own the course must not access its analytics.
        $this->actingAs($mentorB)
            ->getJson('/api/assessments/course/'.$courseA->id.'/stats?timestamp=2026-01-01T00:00:00Z')
            ->assertForbidden();
    }

    public function test_mentor_can_view_own_course_stats(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourse($mentor);

        $this->actingAs($mentor)
            ->getJson('/api/assessments/course/'.$course->id.'/stats?timestamp=2026-01-01T00:00:00Z')
            ->assertOk()
            ->assertJsonPath('status', 'S')
            ->assertJsonPath('data.course_id', $course->id);
    }

    public function test_course_stats_returns_404_for_non_existent_course(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $this->actingAs($admin)
            ->getJson('/api/assessments/course/999999/stats?timestamp=2026-01-01T00:00:00Z')
            ->assertNotFound();
    }
}
