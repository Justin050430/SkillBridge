<?php

/**
 * @author OOI JIAN XIN
 *
 * Feature tests for the Student Dashboard.
 *  - Renders stat cards from student-scoped data.
 *  - Shows recent published assessments for enrolled courses only.
 *  - Empty state when the student has no enrollments.
 */

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
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
            'status' => 'Active',
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

    public function test_student_dashboard_renders_stat_cards(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $this->enroll($student, $course);
        $this->makeAssessment($this->makeLesson($course));

        $this->actingAs($student)
            ->get('/student/dashboard')
            ->assertOk()
            ->assertSee('Student Dashboard')
            ->assertSee('Enrolled Courses')
            ->assertSee('Available Assessments')
            ->assertSee('Continue Learning')
            ->assertSee('Quick Actions');
    }

    public function test_student_dashboard_shows_own_course_assessments_only(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');

        $enrolledCourse = $this->makeCourseFor($mentor);
        $otherCourse = $this->makeCourseFor($mentor);

        $this->enroll($student, $enrolledCourse);

        $assessmentA = $this->makeAssessment($this->makeLesson($enrolledCourse));
        $assessmentB = $this->makeAssessment($this->makeLesson($otherCourse));

        $this->actingAs($student)
            ->get('/student/dashboard')
            ->assertOk()
            ->assertSee($assessmentA->title)
            ->assertDontSee($assessmentB->title);
    }

    public function test_student_dashboard_empty_state_when_no_enrollments(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');

        $this->actingAs($student)
            ->get('/student/dashboard')
            ->assertOk()
            ->assertSee('No assessments available yet. Browse courses to start learning.');
    }
}
