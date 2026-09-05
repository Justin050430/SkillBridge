<?php

/**
 * @author OOI JIAN XIN
 *
 * Notification module tests:
 *  - GoF Command pattern execution (enrollment / result / submission commands).
 *  - Observer -> Command integration on assessment submission.
 *  - Enrollment -> mentor notification trigger.
 *  - Inbox ownership (server-side) and mark-read / mark-all-read.
 */

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use App\Patterns\Command\Commands\NotifyMentorOnAssessmentSubmission;
use App\Patterns\Command\Commands\NotifyMentorOnEnrollment;
use App\Patterns\Command\Commands\NotifyStudentOnAssessmentResult;
use App\Patterns\Command\NotificationInvoker;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationModuleTest extends TestCase
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

    protected function submitAssessment(User $student, Assessment $assessment): void
    {
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

        $this->actingAs($student)->startAssessment($assessment);
        $this->actingAs($student)
            ->post('/student/assessments/'.$assessment->slug.'/submit', ['attempt_id' => $assessment->attempts()->latest('id')->value('id') ?? 1, 'answers' => []]);
    }

    // ---- Command pattern execution ----

    public function test_notify_mentor_on_enrollment_command_creates_notification(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);

        $command = new NotifyMentorOnEnrollment(app(NotificationService::class), $mentor, $student, $course);

        app(NotificationInvoker::class)->run($command);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $mentor->id,
            'title' => 'New enrollment',
            'notification_type' => 'Enrollment',
        ]);
    }

    public function test_notify_student_on_result_command_creates_notification(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $enrollment = $this->enroll($student, $course);
        $assessment = $this->makeAssessment($this->makeLesson($course));

        $attempt = AssessmentAttempt::create([
            'enrollment_id' => $enrollment->id,
            'assessment_id' => $assessment->id,
            'attempt_number' => 1,
            'score' => 1,
            'percentage' => 100.0,
            'is_passed' => true,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $command = new NotifyStudentOnAssessmentResult(app(NotificationService::class), $student, $assessment, $attempt);

        app(NotificationInvoker::class)->run($command);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'title' => 'Assessment result',
            'notification_type' => 'Assessment',
        ]);
    }

    public function test_notify_mentor_on_submission_command_creates_notification(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $assessment = $this->makeAssessment($this->makeLesson($course));

        $command = new NotifyMentorOnAssessmentSubmission(app(NotificationService::class), $mentor, $student, $assessment);

        app(NotificationInvoker::class)->run($command);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $mentor->id,
            'title' => 'Assessment submitted',
            'notification_type' => 'Assessment',
        ]);
    }

    // ---- Integration: enrollment triggers mentor notification ----

    public function test_student_enrollment_notifies_the_mentor(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->actingAs($student)
            ->post('/student/courses/'.$course->slug.'/enroll')
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $mentor->id,
            'notification_type' => 'Enrollment',
            'is_read' => 0,
        ]);
    }

    // ---- Integration: submission notifies student + mentor (Observer -> Command) ----

    public function test_assessment_submission_notifies_student_and_mentor(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $this->enroll($student, $course);
        $assessment = $this->makeAssessment($this->makeLesson($course));

        $this->submitAssessment($student, $assessment);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'title' => 'Assessment result',
            'notification_type' => 'Assessment',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $mentor->id,
            'title' => 'Assessment submitted',
            'notification_type' => 'Assessment',
        ]);
    }

    // ---- Inbox ownership + mark read ----

    public function test_user_only_sees_own_notifications(): void
    {
        $userA = $this->makeUser('Student', 'a@test.com');
        $userB = $this->makeUser('Student', 'b@test.com');

        Notification::create([
            'user_id' => $userB->id,
            'title' => 'Secret to B',
            'message' => 'Private',
            'notification_type' => 'System',
        ]);

        $this->actingAs($userA)
            ->get('/notifications')
            ->assertOk()
            ->assertDontSee('Secret to B');
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $userA = $this->makeUser('Student', 'a@test.com');
        $userB = $this->makeUser('Student', 'b@test.com');

        $notification = Notification::create([
            'user_id' => $userB->id,
            'title' => 'Private',
            'message' => 'Private',
            'notification_type' => 'System',
        ]);

        $this->actingAs($userA)
            ->post('/notifications/'.$notification->id.'/read')
            ->assertForbidden();

        $this->assertFalse((bool) $notification->fresh()->is_read);
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = $this->makeUser('Student', 'student@test.com');

        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => 'Hi',
            'message' => 'Hello',
            'notification_type' => 'System',
        ]);

        $this->actingAs($user)
            ->post('/notifications/'.$notification->id.'/read')
            ->assertRedirect();

        $this->assertTrue((bool) $notification->fresh()->is_read);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = $this->makeUser('Student', 'student@test.com');

        Notification::create(['user_id' => $user->id, 'title' => 'A', 'message' => 'A', 'notification_type' => 'System']);
        Notification::create(['user_id' => $user->id, 'title' => 'B', 'message' => 'B', 'notification_type' => 'System']);

        $this->actingAs($user)
            ->post('/notifications/read-all')
            ->assertRedirect();

        $this->assertEquals(0, Notification::where('user_id', $user->id)->where('is_read', false)->count());
    }
}
