<?php

/**
 * @author OOI JIAN XIN
 *
 * Enrollment module tests:
 *  - GoF Strategy pattern execution (enroll / complete / drop).
 *  - Server-side access control (Admin full oversight; Mentor scoped to
 *    their own courses; students cannot manipulate status).
 */

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use App\Patterns\Strategy\CompleteEnrollmentStrategy;
use App\Patterns\Strategy\DropEnrollmentStrategy;
use App\Patterns\Strategy\EnrollStudentStrategy;
use App\Patterns\Strategy\EnrollmentProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnrollmentManagementTest extends TestCase
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

    protected function enroll(User $student, Course $course): Enrollment
    {
        return Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'Active',
            'enrolled_at' => now(),
        ]);
    }

    // ---- Strategy pattern ----

    public function test_enroll_student_strategy_creates_active_enrollment(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);

        $enrollment = app(EnrollmentProcessor::class)->process(
            app(EnrollStudentStrategy::class),
            ['student_id' => $student->id, 'course_id' => $course->id]
        );

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'Active',
        ]);
    }

    public function test_enroll_student_strategy_is_idempotent(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);

        $processor = app(EnrollmentProcessor::class);

        $processor->process(app(EnrollStudentStrategy::class), ['student_id' => $student->id, 'course_id' => $course->id]);
        $processor->process(app(EnrollStudentStrategy::class), ['student_id' => $student->id, 'course_id' => $course->id]);

        $this->assertEquals(
            1,
            Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->count()
        );
    }

    public function test_complete_enrollment_strategy_marks_completed(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $enrollment = $this->enroll($student, $course);

        app(EnrollmentProcessor::class)->process(
            app(CompleteEnrollmentStrategy::class),
            ['enrollment' => $enrollment]
        );

        $this->assertEquals('Completed', $enrollment->fresh()->status);
        $this->assertNotNull($enrollment->fresh()->completed_at);
    }

    public function test_drop_enrollment_strategy_marks_dropped(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $enrollment = $this->enroll($student, $course);

        app(EnrollmentProcessor::class)->process(
            app(DropEnrollmentStrategy::class),
            ['enrollment' => $enrollment]
        );

        $this->assertEquals('Dropped', $enrollment->fresh()->status);
    }

    public function test_completing_a_dropped_enrollment_is_rejected(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $enrollment = $this->enroll($student, $course);
        $enrollment->update(['status' => 'Dropped']);

        $this->expectException(HttpException::class);

        app(EnrollmentProcessor::class)->process(
            app(CompleteEnrollmentStrategy::class),
            ['enrollment' => $enrollment->fresh()]
        );
    }

    // ---- Access control ----

    public function test_student_cannot_access_enrollment_management(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');

        $this->actingAs($student)
            ->get('/enrollments')
            ->assertForbidden();
    }

    public function test_mentor_only_sees_enrollments_of_own_courses(): void
    {
        $mentorA = $this->makeUser('Mentor', 'mentorA@test.com');
        $mentorB = $this->makeUser('Mentor', 'mentorB@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $courseA = $this->makeCourseFor($mentorA);
        $courseB = $this->makeCourseFor($mentorB);

        $enrollmentA = $this->enroll($student, $courseA);
        $enrollmentB = $this->enroll($student, $courseB);

        $this->actingAs($mentorA)
            ->get('/enrollments')
            ->assertOk()
            ->assertSee($enrollmentA->course->title)
            ->assertDontSee($enrollmentB->course->title);
    }

    public function test_mentor_cannot_complete_another_mentors_enrollment(): void
    {
        $mentorA = $this->makeUser('Mentor', 'mentorA@test.com');
        $mentorB = $this->makeUser('Mentor', 'mentorB@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $courseB = $this->makeCourseFor($mentorB);
        $enrollmentB = $this->enroll($student, $courseB);

        $this->actingAs($mentorA)
            ->post('/enrollments/'.$enrollmentB->id.'/complete')
            ->assertForbidden();

        $this->assertEquals('Active', $enrollmentB->fresh()->status);
    }

    public function test_mentor_can_complete_own_enrollment(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $enrollment = $this->enroll($student, $course);

        $this->actingAs($mentor)
            ->post('/enrollments/'.$enrollment->id.'/complete')
            ->assertRedirect('/enrollments');

        $this->assertEquals('Completed', $enrollment->fresh()->status);
    }

    public function test_admin_can_manage_any_enrollment(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $enrollment = $this->enroll($student, $course);

        $this->actingAs($admin)
            ->post('/enrollments/'.$enrollment->id.'/drop')
            ->assertRedirect('/enrollments');

        $this->assertEquals('Dropped', $enrollment->fresh()->status);
    }
}
