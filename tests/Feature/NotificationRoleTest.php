<?php

/**
 * @author OOI JIAN XIN
 *
 * Role-scoped notification tests:
 *  - Student enrollment confirmation (student + relevant mentor notified).
 *  - Recipient scoping: mentors are never notified for other mentors' courses.
 *  - Security notifications (password change, login lockout, deactivation).
 *  - related_type / related_id stored and resolved into an actionable link.
 */

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationRoleTest extends TestCase
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

    // ---- Enrollment confirmation: student + relevant mentor ----

    public function test_student_and_mentor_are_notified_on_enrollment(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->actingAs($student)
            ->post('/student/courses/'.$course->slug.'/enroll')
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'title' => 'Enrollment confirmed',
            'notification_type' => 'Enrollment',
            'related_type' => Course::class,
            'related_id' => $course->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $mentor->id,
            'title' => 'New enrollment',
            'notification_type' => 'Enrollment',
        ]);
    }

    public function test_mentor_is_not_notified_for_another_mentors_course(): void
    {
        $mentorA = $this->makeUser('Mentor', 'a@test.com');
        $mentorB = $this->makeUser('Mentor', 'b@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentorA);

        $this->actingAs($student)
            ->post('/student/courses/'.$course->slug.'/enroll')
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', ['user_id' => $mentorA->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $mentorB->id]);
    }

    // ---- Security notifications ----

    public function test_password_change_sends_security_notification_to_user(): void
    {
        $user = $this->makeUser('Student', 'student@test.com');

        $this->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Password changed',
            'notification_type' => 'Security',
        ]);
    }

    public function test_admins_are_notified_on_login_lockout(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $this->makeUser('Student', 'victim@test.com');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'victim@test.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'title' => 'Failed login lockout',
            'notification_type' => 'Security',
        ]);
    }

    public function test_admins_are_notified_when_a_user_is_deactivated(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $target = $this->makeUser('Student', 'victim@test.com');

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'title' => 'Account deactivated',
            'notification_type' => 'Security',
            'related_type' => User::class,
            'related_id' => $target->id,
        ]);
    }

    // ---- related_type / related_id link resolution ----

    public function test_related_route_resolves_per_role(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $course = $this->makeCourseFor($mentor);

        $notification = Notification::create([
            'user_id' => $student->id,
            'title' => 'Enrollment confirmed',
            'message' => 'ok',
            'notification_type' => 'Enrollment',
            'related_type' => Course::class,
            'related_id' => $course->id,
        ]);

        $this->assertSame(route('student.courses'), $notification->relatedRoute($student));
        $this->assertSame(route('admin.courses.edit', $course), $notification->relatedRoute($admin));
        $this->assertSame(route('mentor.courses.edit', $course), $notification->relatedRoute($mentor));

        // A different mentor who does not own the course gets no link.
        $otherMentor = $this->makeUser('Mentor', 'other@test.com');
        $this->assertNull($notification->relatedRoute($otherMentor));

        // No related record -> no link.
        $notification->update(['related_id' => 99999]);
        $this->assertNull($notification->fresh()->relatedRoute($student));
    }
}
