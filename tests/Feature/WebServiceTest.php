<?php

/**
 * @author OOI JIAN XIN
 *
 * Verifies the web services for the Assessment & Progress module:
 *  - EXPOSE : GET /api/assessments/{assessment}/stats (IFA-compliant)
 *  - CONSUME: CourseApiClient reads the Course module's published-courses API
 */

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use App\Services\CourseApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebServiceTest extends TestCase
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

    protected function makeCourseFor(User $mentor): Course
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

    protected function enroll(User $student, Course $course): Enrollment
    {
        return Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'Active',
            'enrolled_at' => now(),
        ]);
    }

    public function test_assessment_stats_endpoint_is_ifa_compliant(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course);
        $assessment = $this->makeAssessment($lesson);
        $enrollment = $this->enroll($student, $course);

        AssessmentAttempt::create([
            'enrollment_id' => $enrollment->id,
            'assessment_id' => $assessment->id,
            'attempt_number' => 1,
            'score' => 2,
            'percentage' => 66.67,
            'is_passed' => true,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($mentor)
            ->getJson('/api/assessments/'.$assessment->id.'/stats?timestamp=2026-01-01T00:00:00Z');

        $response->assertOk();
        $response->assertJson([
            'status' => 'S',
            'requestId' => true,
            'timestamp' => true,
        ]);
        $response->assertJsonPath('data.assessment_id', $assessment->id);
        $response->assertJsonPath('data.attempt_count', 1);
        $response->assertJsonPath('data.pass_rate', 100);
        $response->assertJsonPath('data.average_score', 66.67);
    }

    public function test_assessment_stats_requires_timestamp_or_request_id(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $assessment = $this->makeAssessment($this->makeLesson($course));

        $response = $this->actingAs($mentor)
            ->getJson('/api/assessments/'.$assessment->id.'/stats');

        $response->assertStatus(422);
        $response->assertJsonPath('status', 'F');
        $response->assertJsonPath('message', 'A timestamp or requestId field is required.');
    }

    public function test_assessment_stats_blocks_students_and_other_mentors(): void
    {
        $mentorA = $this->makeUser('Mentor', 'mentorA@test.com');
        $mentorB = $this->makeUser('Mentor', 'mentorB@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $courseA = $this->makeCourseFor($mentorA);
        $assessmentA = $this->makeAssessment($this->makeLesson($courseA));

        // A student is forbidden.
        $this->actingAs($student)
            ->getJson('/api/assessments/'.$assessmentA->id.'/stats?timestamp=2026-01-01T00:00:00Z')
            ->assertForbidden();

        // A mentor who does not own the assessment is forbidden.
        $this->actingAs($mentorB)
            ->getJson('/api/assessments/'.$assessmentA->id.'/stats?timestamp=2026-01-01T00:00:00Z')
            ->assertForbidden();
    }

    public function test_course_api_client_parses_ifa_response(): void
    {
        Http::fake([
            '*/api/courses/published*' => Http::response([
                'status' => 'S',
                'requestId' => 'req-123',
                'timestamp' => now()->toIso8601String(),
                'data' => [
                    ['id' => 1, 'title' => 'Course One', 'slug' => 'course-one', 'description' => 'Desc', 'category' => 'Cat', 'mentor' => 'Mentor A'],
                    ['id' => 2, 'title' => 'Course Two', 'slug' => 'course-two', 'description' => 'Desc', 'category' => 'Cat', 'mentor' => 'Mentor B'],
                ],
            ], 200),
        ]);

        $client = app(CourseApiClient::class);
        $courses = $client->publishedCourses();

        $this->assertCount(2, $courses);
        $this->assertEquals('Course One', $courses[0]['title']);
    }

    public function test_result_page_shows_courses_consumed_from_course_api(): void
    {
        Http::fake([
            '*/api/courses/published*' => Http::response([
                'status' => 'S',
                'requestId' => 'req-456',
                'timestamp' => now()->toIso8601String(),
                'data' => [
                    ['id' => 1, 'title' => 'Advanced Phishing Defense', 'slug' => 'phishing', 'description' => 'Desc', 'category' => 'Security', 'mentor' => 'Mentor A'],
                ],
            ], 200),
        ]);

        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $this->enroll($student, $course);
        $assessment = $this->makeAssessment($this->makeLesson($course));

        Question::create([
            'assessment_id' => $assessment->id,
            'question_text' => 'Q?',
            'question_type' => 'MCQ',
            'option_a' => 'A',
            'option_b' => 'B',
            'correct_answer' => 'A',
            'marks' => 1,
            'order_number' => 1,
        ]);

        $this->actingAs($student)->get('/student/assessments/'.$assessment->slug.'/take');
        $this->actingAs($student)
            ->post('/student/assessments/'.$assessment->slug.'/submit', ['answers' => []]);

        $this->actingAs($student)
            ->get('/student/assessments/'.$assessment->slug.'/result')
            ->assertOk()
            ->assertSee('Explore More Courses')
            ->assertSee('Advanced Phishing Defense');
    }
}
