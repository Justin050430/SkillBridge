<?php

/**
 * @author OOI JIAN XIN
 *
 * Notification delete feature tests:
 *  - Users can permanently delete their own read and unread notifications.
 *  - Deleted notifications leave the inbox and stop counting as unread.
 *  - Server-side ownership: a user cannot delete another user's notification.
 *  - Existing mark-read / mark-all-read flows still work.
 *  - Command + Observer notification creation still works after the change.....
 */

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationDeleteTest extends TestCase
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

    protected function createNotification(User $user, string $title, bool $read = false): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => 'Message for '.$title,
            'notification_type' => 'System',
            'is_read' => $read,
            'read_at' => $read ? now() : null,
        ]);
    }

    // ---- Delete own notifications (read + unread) ----

    public function test_user_can_delete_own_unread_notification(): void
    {
        $user = $this->makeUser('Student', 'student@test.com');
        $notification = $this->createNotification($user, 'Unread note');

        $this->actingAs($user)
            ->delete('/notifications/'.$notification->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_user_can_delete_own_read_notification(): void
    {
        $user = $this->makeUser('Student', 'student@test.com');
        $notification = $this->createNotification($user, 'Read note', true);

        $this->actingAs($user)
            ->delete('/notifications/'.$notification->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_user_can_delete_both_read_and_unread_notifications(): void
    {
        $user = $this->makeUser('Student', 'student@test.com');
        $unread = $this->createNotification($user, 'Unread note');
        $read = $this->createNotification($user, 'Read note', true);

        $this->actingAs($user)
            ->delete('/notifications/'.$unread->id)
            ->assertRedirect();

        $this->actingAs($user)
            ->delete('/notifications/'.$read->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', ['id' => $unread->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $read->id]);
        $this->assertEquals(0, Notification::where('user_id', $user->id)->count());
    }

    // ---- Effect on inbox + unread count ----

    public function test_deleted_notification_no_longer_appears_in_inbox(): void
    {
        $user = $this->makeUser('Student', 'student@test.com');
        $notification = $this->createNotification($user, 'Vanishing notification');

        $this->actingAs($user)
            ->delete('/notifications/'.$notification->id)
            ->assertRedirect();

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertDontSee('Vanishing notification');
    }

    public function test_deleted_unread_notification_no_longer_counts_as_unread(): void
    {
        $user = $this->makeUser('Student', 'student@test.com');
        $unread = $this->createNotification($user, 'Unread note');
        $this->createNotification($user, 'Read note', true);

        $this->assertEquals(1, Notification::where('user_id', $user->id)->where('is_read', false)->count());

        $this->actingAs($user)
            ->delete('/notifications/'.$unread->id)
            ->assertRedirect();

        $this->assertEquals(0, Notification::where('user_id', $user->id)->where('is_read', false)->count());
    }

    // ---- Ownership (server-side) ----

    public function test_user_cannot_delete_another_users_notification(): void
    {
        $userA = $this->makeUser('Student', 'a@test.com');
        $userB = $this->makeUser('Student', 'b@test.com');
        $notification = $this->createNotification($userB, 'B private note');

        $this->actingAs($userA)
            ->delete('/notifications/'.$notification->id)
            ->assertForbidden();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $userB->id,
        ]);
    }

    // ---- Existing mark-read still works ----

    public function test_mark_read_still_works(): void
    {
        $user = $this->makeUser('Student', 'student@test.com');
        $notification = $this->createNotification($user, 'To read');

        $this->actingAs($user)
            ->post('/notifications/'.$notification->id.'/read')
            ->assertRedirect();

        $this->assertTrue((bool) $notification->fresh()->is_read);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_still_works(): void
    {
        $user = $this->makeUser('Student', 'student@test.com');
        $this->createNotification($user, 'A');
        $this->createNotification($user, 'B');

        $this->actingAs($user)
            ->post('/notifications/read-all')
            ->assertRedirect();

        $this->assertEquals(0, Notification::where('user_id', $user->id)->where('is_read', false)->count());
    }

    // ---- Command creation still works (and its rows are deletable) ----

    public function test_command_created_notification_still_created_and_deletable(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);

        // Student enrollment dispatches the mentor notification via the
        // Command pattern (NotifyMentorOnEnrollment through the Invoker).
        $this->actingAs($student)
            ->post('/student/courses/'.$course->slug.'/enroll')
            ->assertRedirect();

        $mentorNotification = Notification::where('user_id', $mentor->id)->first();
        $this->assertNotNull($mentorNotification);

        $this->actingAs($mentor)
            ->delete('/notifications/'.$mentorNotification->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', ['id' => $mentorNotification->id]);
    }

    // ---- Observer -> Command creation still works ----

    public function test_observer_dispatched_notifications_still_created_and_scoped_deletable(): void
    {
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

        // Submission fires the Observer, which dispatches notification
        // commands for both the student result and the mentor.
        $this->actingAs($student)
            ->startAssessment($assessment);
        $this->actingAs($student)
            ->post('/student/assessments/'.$assessment->slug.'/submit', ['attempt_id' => $assessment->attempts()->latest('id')->value('id') ?? 1, 'answers' => []]);

        $studentNotification = Notification::where('user_id', $student->id)->first();
        $mentorNotification = Notification::where('user_id', $mentor->id)->first();

        $this->assertNotNull($studentNotification);
        $this->assertNotNull($mentorNotification);

        // Student deletes their own result notification.
        $this->actingAs($student)
            ->delete('/notifications/'.$studentNotification->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', ['id' => $studentNotification->id]);

        // The mentor's notification is untouched by the student's delete.
        $this->assertDatabaseHas('notifications', ['id' => $mentorNotification->id]);
    }
}
