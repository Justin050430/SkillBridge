<?php

/**
 * @author OOI JIAN XIN
 *
 * Feature tests for Mentor Course Management.
 *  - Mentor scoping: only own courses in the listing, 403 on other mentors'.
 *  - mentor_id is always derived from the authenticated mentor server-side.
 *  - CRUD on own courses; deletion blocked when dependent records exist.
 *  - Students are blocked from mentor course-management routes.
 */

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MentorCourseManagementTest extends TestCase
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

    protected function makeCourseFor(User $mentor, array $overrides = []): Course
    {
        $category = Category::create(['category_name' => 'Category '.uniqid()]);

        return Course::create(array_merge([
            'mentor_id' => $mentor->id,
            'category_id' => $category->id,
            'title' => 'Course '.uniqid(),
            'slug' => 'course-'.uniqid(),
            'description' => 'Test course',
            'status' => 'Draft',
        ], $overrides));
    }

    public function test_students_are_blocked_from_mentor_course_management(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->actingAs($student)
            ->get('/mentor/courses')
            ->assertForbidden();

        $this->actingAs($student)
            ->get('/mentor/courses/'.$course->slug.'/edit')
            ->assertForbidden();
    }

    public function test_mentor_only_sees_their_own_courses(): void
    {
        $mentorA = $this->makeUser('Mentor', 'mentorA@test.com');
        $mentorB = $this->makeUser('Mentor', 'mentorB@test.com');

        $courseA = $this->makeCourseFor($mentorA);
        $courseB = $this->makeCourseFor($mentorB);

        $this->actingAs($mentorA)
            ->get('/mentor/courses')
            ->assertOk()
            ->assertSee($courseA->title)
            ->assertDontSee($courseB->title);
    }

    public function test_mentor_cannot_access_another_mentors_course_by_slug(): void
    {
        $mentorA = $this->makeUser('Mentor', 'mentorA@test.com');
        $mentorB = $this->makeUser('Mentor', 'mentorB@test.com');
        $courseB = $this->makeCourseFor($mentorB);

        $this->actingAs($mentorA)
            ->get('/mentor/courses/'.$courseB->slug.'/edit')
            ->assertForbidden();

        $this->actingAs($mentorA)
            ->get('/mentor/courses/'.$courseB->slug.'/delete')
            ->assertForbidden();
    }

    public function test_mentor_cannot_update_another_mentors_course(): void
    {
        $mentorA = $this->makeUser('Mentor', 'mentorA@test.com');
        $mentorB = $this->makeUser('Mentor', 'mentorB@test.com');
        $courseB = $this->makeCourseFor($mentorB);

        $this->actingAs($mentorA)
            ->put('/mentor/courses/'.$courseB->slug, [
                'category_id' => $courseB->category_id,
                'title' => 'Hacked Title',
                'description' => 'Hacked',
                'status' => 'Published',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('courses', [
            'id' => $courseB->id,
            'title' => $courseB->title,
        ]);
    }

    public function test_mentor_cannot_delete_another_mentors_course(): void
    {
        $mentorA = $this->makeUser('Mentor', 'mentorA@test.com');
        $mentorB = $this->makeUser('Mentor', 'mentorB@test.com');
        $courseB = $this->makeCourseFor($mentorB);

        $this->actingAs($mentorA)
            ->delete('/mentor/courses/'.$courseB->slug)
            ->assertForbidden();

        $this->assertDatabaseHas('courses', ['id' => $courseB->id]);
    }

    public function test_mentor_cannot_assign_course_to_another_mentor_through_request(): void
    {
        $mentorA = $this->makeUser('Mentor', 'mentorA@test.com');
        $mentorB = $this->makeUser('Mentor', 'mentorB@test.com');

        // Even if a malicious mentor_id is posted, it must be ignored.
        $this->actingAs($mentorA)
            ->post('/mentor/courses', [
                'mentor_id' => $mentorB->id,
                'category_id' => Category::create(['category_name' => 'Cat'])->id,
                'title' => 'My New Course',
                'description' => 'Description',
                'status' => 'Published',
            ])
            ->assertRedirect('/mentor/courses');

        $this->assertDatabaseHas('courses', [
            'title' => 'My New Course',
            'mentor_id' => $mentorA->id,
        ]);

        $this->assertDatabaseMissing('courses', [
            'title' => 'My New Course',
            'mentor_id' => $mentorB->id,
        ]);
    }

    public function test_mentor_can_create_own_course(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $category = Category::create(['category_name' => 'Programming']);

        $this->actingAs($mentor)
            ->post('/mentor/courses', [
                'category_id' => $category->id,
                'title' => 'New Course',
                'description' => 'Description',
                'status' => 'Draft',
            ])
            ->assertRedirect('/mentor/courses');

        $this->assertDatabaseHas('courses', [
            'title' => 'New Course',
            'mentor_id' => $mentor->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_mentor_can_update_own_course(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->actingAs($mentor)
            ->put('/mentor/courses/'.$course->slug, [
                'category_id' => $course->category_id,
                'title' => 'Updated Title',
                'description' => 'Updated description',
                'status' => 'Published',
            ])
            ->assertRedirect('/mentor/courses');

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Updated Title',
            'status' => 'Published',
        ]);
    }

    public function test_mentor_can_delete_own_course_without_dependents(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->actingAs($mentor)
            ->delete('/mentor/courses/'.$course->slug)
            ->assertRedirect('/mentor/courses');

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_mentor_cannot_delete_course_with_lessons(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'content' => 'Content',
            'order_number' => 1,
            'status' => 'Published',
        ]);

        $this->actingAs($mentor)
            ->delete('/mentor/courses/'.$course->slug)
            ->assertRedirect('/mentor/courses');

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_mentor_cannot_delete_course_with_enrollments(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);

        Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'Active',
            'enrolled_at' => now(),
        ]);

        $this->actingAs($mentor)
            ->delete('/mentor/courses/'.$course->slug)
            ->assertRedirect('/mentor/courses');

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }
}
