<?php

/**
 * @author OOI JIAN XIN
 *
 * Student self-enrollment flow tests (AJAX + statuses):
 *  - Enroll (creates Active), duplicate prevention (idempotent).
 *  - Enrolled status is passed to the course catalogue view.
 *  - Student self-drop (Active -> Dropped) and re-enrolment (reactivate).
 *  - Ownership: a student cannot touch another student's enrollment.
 *  - Invalid / non-existent courses are rejected.
 */

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentEnrollmentFlowTest extends TestCase
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

    public function test_student_can_enroll_via_ajax(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->actingAs($student)
            ->postJson('/student/courses/'.$course->slug.'/enroll')
            ->assertOk()
            ->assertJson([
                'status' => 'S',
                'enrollment_status' => 'Active',
            ]);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'Active',
        ]);
    }

    public function test_duplicate_enrollment_is_prevented(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->actingAs($student)->postJson('/student/courses/'.$course->slug.'/enroll')->assertOk();
        $this->actingAs($student)->postJson('/student/courses/'.$course->slug.'/enroll')->assertOk();

        $this->assertEquals(1, Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->count());
    }

    public function test_enrolled_status_is_passed_to_the_catalogue_view(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->actingAs($student)->postJson('/student/courses/'.$course->slug.'/enroll')->assertOk();

        $this->actingAs($student)
            ->get('/student/courses')
            ->assertOk()
            ->assertViewHas('enrollments', function ($enrollments) use ($course) {
                return ($enrollments->get($course->id) ?? null) === 'Active';
            });
    }

    public function test_student_can_drop_own_course(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->actingAs($student)->postJson('/student/courses/'.$course->slug.'/enroll')->assertOk();

        $this->actingAs($student)
            ->postJson('/student/courses/'.$course->slug.'/drop')
            ->assertOk()
            ->assertJson([
                'status' => 'S',
                'enrollment_status' => 'Dropped',
            ]);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'Dropped',
        ]);
    }

    public function test_student_can_reenroll_after_dropping(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->actingAs($student)->postJson('/student/courses/'.$course->slug.'/enroll')->assertOk();
        $this->actingAs($student)->postJson('/student/courses/'.$course->slug.'/drop')->assertOk();

        $this->actingAs($student)
            ->postJson('/student/courses/'.$course->slug.'/enroll')
            ->assertOk()
            ->assertJson(['status' => 'S', 'enrollment_status' => 'Active']);

        // Unique (student_id, course_id) constraint is respected - the same row
        // is reactivated, never duplicated.
        $this->assertEquals(1, Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->count());
        $this->assertSame('Active', Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->first()->status);
    }

    public function test_student_cannot_drop_someone_elses_enrollment(): void
    {
        $studentA = $this->makeUser('Student', 'a@test.com');
        $studentB = $this->makeUser('Student', 'b@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->actingAs($studentA)->postJson('/student/courses/'.$course->slug.'/enroll')->assertOk();

        // Student B has no active enrollment in this course -> 404.
        $this->actingAs($studentB)
            ->postJson('/student/courses/'.$course->slug.'/drop')
            ->assertNotFound();

        // A's enrollment is untouched.
        $this->assertSame('Active', Enrollment::where('student_id', $studentA->id)->where('course_id', $course->id)->first()->status);
    }

    public function test_non_existent_course_cannot_be_enrolled_in(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');

        $this->actingAs($student)
            ->postJson('/student/courses/this-course-does-not-exist/enroll')
            ->assertNotFound();
    }

    public function test_drop_requires_an_active_enrollment(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->actingAs($student)
            ->postJson('/student/courses/'.$course->slug.'/drop')
            ->assertNotFound();
    }
}
