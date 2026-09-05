<?php

/**
 * @author OOI JIAN XIN
 *
 * Feature tests for the student enrolment, take-assessment and progress flows.
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
use App\Models\StudentAnswer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssessmentTimerTest extends TestCase
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

    protected function makeLesson(Course $course, int $order = 1): Lesson
    {
        return Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson '.uniqid(),
            'content' => 'Content',
            'order_number' => $order,
            'status' => 'Published',
        ]);
    }

    protected function makeAssessment(Lesson $lesson, array $overrides = []): Assessment
    {
        return Assessment::create(array_merge([
            'lesson_id' => $lesson->id,
            'title' => 'Assessment '.uniqid(),
            'passing_score' => 50,
            'status' => 'Published',
        ], $overrides));
    }

    protected function makeQuestion(Assessment $assessment, array $overrides = []): Question
    {
        return Question::create(array_merge([
            'assessment_id' => $assessment->id,
            'question_text' => 'Question?',
            'question_type' => 'MCQ',
            'option_a' => 'Option A',
            'option_b' => 'Option B',
            'option_c' => 'Option C',
            'option_d' => 'Option D',
            'correct_answer' => 'A',
            'marks' => 1,
            'order_number' => 1,
        ], $overrides));
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

    private function timerFixture(?int $seconds = 10, ?int $limit = 3): array
    {
        $student = $this->makeUser('Student', uniqid().'@student.test');
        $mentor = $this->makeUser('Mentor', uniqid().'@mentor.test');
        $course = $this->makeCourseFor($mentor);
        $enrollment = $this->enroll($student, $course);
        $assessment = $this->makeAssessment($this->makeLesson($course), [
            'time_limit' => $seconds, 'max_attempts' => $limit,
        ]);
        $question = $this->makeQuestion($assessment);
        $this->actingAs($student);

        return [$student, $assessment, $enrollment, $question];
    }

    private function begin(Assessment $assessment): AssessmentAttempt
    {
        $response = $this->post(route('student.assessments.start', $assessment));
        $attempt = $assessment->attempts()->latest('attempt_number')->firstOrFail();
        $response->assertRedirect(route('student.assessments.take', ['assessment' => $assessment, 'attempt' => $attempt->id]));

        return $attempt;
    }

    private function takeUrl(Assessment $assessment, AssessmentAttempt $attempt): string
    {
        return route('student.assessments.take', ['assessment' => $assessment, 'attempt' => $attempt->id]);
    }

    private function resultUrl(Assessment $assessment, AssessmentAttempt $attempt): string
    {
        return route('student.assessments.result', ['assessment' => $assessment, 'attempt' => $attempt->id]);
    }

    public function test_get_never_starts_an_attempt_and_refresh_preserves_deadline(): void
    {
        $this->freezeTime();
        [, $assessment] = $this->timerFixture(330);
        $this->get(route('student.assessments.take', $assessment))->assertRedirect(route('student.assessments.index'));
        $this->assertSame(0, $assessment->attempts()->count());
        $attempt = $this->begin($assessment);
        $this->assertTrue($attempt->expires_at->equalTo($attempt->started_at->copy()->addSeconds(330)));
        $this->travel(20)->seconds();
        foreach (range(1, 3) as $tab) {
            $this->get($this->takeUrl($assessment, $attempt))->assertOk()->assertSee('Time remaining')
                ->assertSee('name="attempt_id" value="'.$attempt->id.'"', false)
                ->assertHeader('Cache-Control', 'no-store, private');
        }
        $this->assertTrue($attempt->fresh()->started_at->equalTo($attempt->started_at));
        $this->assertTrue($attempt->fresh()->expires_at->equalTo($attempt->expires_at));
        $this->assertSame($attempt->id, $this->begin($assessment)->id);
        $this->assertSame(1, $assessment->attempts()->count());
    }

    public function test_submission_before_deadline_grades_once_and_expiry_cannot_undo_it(): void
    {
        $this->freezeTime();
        [, $assessment, $enrollment, $question] = $this->timerFixture();
        $attempt = $this->begin($assessment);
        $this->travelTo($attempt->expires_at->copy()->subSecond());
        $payload = ['attempt_id' => $attempt->id, 'answers' => [$question->id => 'A']];
        $this->post(route('student.assessments.submit', $assessment), $payload)->assertRedirect();
        $this->assertTrue($attempt->fresh()->is_passed);
        $submittedAt = $attempt->fresh()->submitted_at;
        $notificationCount = Notification::count();
        $this->travel(5)->seconds();
        $this->post(route('student.assessments.submit', $assessment), $payload)->assertRedirect($this->resultUrl($assessment, $attempt));
        $this->postJson(route('student.assessments.expire', $assessment), ['attempt_id' => $attempt->id])
            ->assertOk()->assertJsonPath('state', 'submitted');
        $this->assertSame(1, $attempt->studentAnswers()->count());
        $this->assertNull($attempt->fresh()->expired_at);
        $this->assertTrue($attempt->fresh()->submitted_at->equalTo($submittedAt));
        $this->assertSame($notificationCount, Notification::count());
        $this->assertDatabaseHas('progress', ['enrollment_id' => $enrollment->id, 'status' => 'Completed']);
    }

    #[DataProvider('lateSubmissions')]
    public function test_exact_and_late_submissions_expire_without_accepting_answers(int $lateBy): void
    {
        $this->freezeTime();
        [, $assessment, , $question] = $this->timerFixture();
        // Even a zero passing threshold must not turn a timeout into a pass.
        $assessment->update(['passing_score' => 0]);
        $attempt = $this->begin($assessment);
        $this->travelTo($attempt->expires_at->copy()->addSeconds($lateBy));
        $notifications = Notification::count();
        $this->post(route('student.assessments.submit', $assessment), [
            'attempt_id' => $attempt->id, 'answers' => [$question->id => 'A'],
            'remaining_seconds' => 9999, 'started_at' => now(), 'expires_at' => now()->addHour(),
        ])->assertRedirect($this->resultUrl($assessment, $attempt));
        $attempt->refresh();
        $this->assertTrue($attempt->hasExpired());
        $this->assertTrue($attempt->expired_at->equalTo($attempt->expires_at));
        $this->assertNull($attempt->submitted_at);
        $this->assertFalse($attempt->is_passed);
        $this->assertSame(0, $attempt->studentAnswers()->count());
        $this->assertDatabaseCount('progress', 0);
        $this->assertSame($notifications, Notification::count());
    }

    public static function lateSubmissions(): array
    {
        return ['exactly zero' => [0], 'one second late' => [1], 'five seconds late' => [5]];
    }

    public function test_malformed_late_answers_still_end_the_attempt(): void
    {
        $this->freezeTime();
        [, $assessment] = $this->timerFixture();
        $attempt = $this->begin($assessment);
        $this->travelTo($attempt->expires_at);
        $this->post(route('student.assessments.submit', $assessment), [
            'attempt_id' => $attempt->id, 'answers' => 'invalid',
        ])->assertRedirect($this->resultUrl($assessment, $attempt))->assertSessionHasNoErrors();
        $this->assertNotNull($attempt->fresh()->expired_at);
    }

    public function test_active_invalid_answers_are_rejected_without_losing_deadline(): void
    {
        [, $assessment] = $this->timerFixture(600);
        $attempt = $this->begin($assessment);
        $this->from($this->takeUrl($assessment, $attempt))->post(route('student.assessments.submit', $assessment), [
            'attempt_id' => $attempt->id, 'answers' => ['1' => ['invalid']],
        ])->assertSessionHasErrors('answers.1')->assertRedirect($this->takeUrl($assessment, $attempt));
        $this->assertTrue($attempt->fresh()->isActive());
        $this->assertSame(0, $attempt->studentAnswers()->count());
    }

    public function test_timeout_needs_no_browser_request_and_never_automatically_allocates_retry(): void
    {
        $this->freezeTime();
        [, $assessment] = $this->timerFixture();
        $attempt = $this->begin($assessment);
        $this->travel(60)->minutes();
        $response = $this->get(route('student.assessments.index'))->assertOk()->assertSee('Retake Assessment');
        $summary = $response->viewData('summaries')->first();
        $this->assertSame(1, $summary->attemptCount);
        $this->assertFalse($summary->hasInProgress);
        $this->get($this->takeUrl($assessment, $attempt))->assertRedirect($this->resultUrl($assessment, $attempt));
        $this->get($this->resultUrl($assessment, $attempt))->assertOk()
            ->assertSee('Time is up')->assertSee('2 new attempt(s) remaining.')
            ->assertDontSee('Answer Review')->assertDontSee('Correct answer');
        $this->assertSame(1, $assessment->attempts()->count());
        $this->assertNotNull($attempt->fresh()->expired_at);
        $this->assertSame(2, $this->begin($assessment)->attempt_number);
    }

    public function test_three_timeouts_exhaust_three_attempts_and_stale_pages_cannot_allocate_fourth(): void
    {
        $this->freezeTime();
        [, $assessment] = $this->timerFixture();
        foreach (range(1, 3) as $number) {
            $attempt = $this->begin($assessment);
            $this->assertSame($number, $attempt->attempt_number);
            $this->travelTo($attempt->expires_at);
            $this->postJson(route('student.assessments.expire', $assessment), ['attempt_id' => $attempt->id])
                ->assertOk()->assertJsonPath('state', 'expired');
        }
        $this->post(route('student.assessments.start', $assessment))
            ->assertRedirect(route('student.assessments.index'))->assertSessionHas('error');
        $this->get($this->takeUrl($assessment, $attempt))->assertRedirect($this->resultUrl($assessment, $attempt));
        $this->get(route('student.assessments.index'))->assertSee('Attempts used: 3')->assertSee('No attempts left');
        $this->get($this->resultUrl($assessment, $attempt))->assertSee('No attempts left')->assertDontSee('>Retake<', false);
        $this->assertSame(3, $assessment->attempts()->count());
    }

    public function test_old_tab_submission_and_expiry_do_not_change_retry(): void
    {
        $this->freezeTime();
        [, $assessment, , $question] = $this->timerFixture();
        $old = $this->begin($assessment);
        $this->travelTo($old->expires_at);
        $retry = $this->begin($assessment);
        $this->post(route('student.assessments.submit', $assessment), [
            'attempt_id' => $old->id, 'answers' => [$question->id => 'A'],
        ])->assertRedirect($this->resultUrl($assessment, $old));
        $this->postJson(route('student.assessments.expire', $assessment), ['attempt_id' => $old->id])
            ->assertJsonPath('state', 'expired');
        $this->assertTrue($retry->fresh()->isActive());
        $this->assertSame(0, $retry->studentAnswers()->count());
        $this->assertSame(2, $assessment->attempts()->count());
    }

    public function test_early_expire_request_and_duration_edit_cannot_change_existing_deadline(): void
    {
        $this->freezeTime();
        [, $assessment] = $this->timerFixture();
        $attempt = $this->begin($assessment);
        $assessment->update(['time_limit' => null]);
        $this->postJson(route('student.assessments.expire', $assessment), [
            'attempt_id' => $attempt->id, 'expires_at' => now()->subHour(),
        ])->assertJsonPath('state', 'active')->assertJsonPath('expires_at', $attempt->expires_at->getTimestampMs());
        $this->travelTo($attempt->expires_at);
        $this->post(route('student.assessments.submit', $assessment), ['attempt_id' => $attempt->id])
            ->assertRedirect($this->resultUrl($assessment, $attempt));
        $retry = $this->begin($assessment);
        $this->assertNull($retry->expires_at);
    }

    public function test_untimed_assessment_has_no_countdown_and_can_submit_after_days(): void
    {
        $this->freezeTime();
        [, $assessment] = $this->timerFixture(null, null);
        $attempt = $this->begin($assessment);
        $this->assertNull($attempt->expires_at);
        $this->travel(3)->days();
        $this->get($this->takeUrl($assessment, $attempt))->assertOk()->assertDontSee('Time remaining');
        $this->post(route('student.assessments.submit', $assessment), ['attempt_id' => $attempt->id])->assertRedirect();
        $this->assertNotNull($attempt->fresh()->submitted_at);
        $this->assertNull($attempt->fresh()->expired_at);
    }

    public function test_missing_or_foreign_attempt_ids_cannot_submit_or_expire(): void
    {
        [$student, $assessment, $enrollment] = $this->timerFixture();
        $attempt = $this->begin($assessment);
        $other = $this->makeUser('Student', 'other@student.test');
        $this->enroll($other, $enrollment->course);
        $this->actingAs($other);
        foreach (['submit', 'expire'] as $action) {
            $this->postJson(route('student.assessments.'.$action, $assessment), ['attempt_id' => $attempt->id])->assertForbidden();
            $this->postJson(route('student.assessments.'.$action, $assessment), [])->assertUnprocessable();
        }
        $this->get($this->takeUrl($assessment, $attempt))->assertForbidden();
        $this->get($this->resultUrl($assessment, $attempt))->assertNotFound();
        $this->assertTrue($attempt->fresh()->isActive());
    }

    public function test_expired_attempts_are_in_dashboard_count_but_not_graded_analytics(): void
    {
        $this->freezeTime();
        [, $assessment, $enrollment] = $this->timerFixture();
        $attempt = $this->begin($assessment);
        $this->travelTo($attempt->expires_at);
        $this->get('/student/dashboard')->assertOk()->assertViewHas('attemptsTaken', 1);
        $this->actingAs($enrollment->course->mentor);
        $this->getJson(route('api.assessments.stats', $assessment).'?requestId=timer-test')
            ->assertOk()->assertJsonPath('data.attempt_count', 0)->assertJsonPath('data.average_score', null);
    }

    public function test_existing_minutes_are_migrated_and_open_attempts_receive_deadlines(): void
    {
        $this->freezeTime();
        [, $assessment, $enrollment] = $this->timerFixture(null);
        $migration = require database_path('migrations/2026_09_05_000000_add_assessment_deadlines.php');
        $migration->down();
        $assessment->update(['time_limit' => 30]);
        $old = AssessmentAttempt::create([
            'assessment_id' => $assessment->id, 'enrollment_id' => $enrollment->id,
            'attempt_number' => 1, 'score' => 0, 'percentage' => 0, 'is_passed' => false,
            'started_at' => now()->subHour(),
        ]);
        $recent = AssessmentAttempt::create([
            'assessment_id' => $assessment->id, 'enrollment_id' => $enrollment->id,
            'attempt_number' => 2, 'score' => 0, 'percentage' => 0, 'is_passed' => false,
            'started_at' => now()->subMinutes(5),
        ]);
        $migration->up();
        $this->assertSame(1800, $assessment->fresh()->time_limit);
        $this->assertTrue($old->fresh()->hasExpired());
        $this->assertTrue($old->fresh()->expired_at->equalTo($old->started_at->copy()->addMinutes(30)));
        $this->assertTrue($recent->fresh()->isActive());
        $this->assertTrue($recent->fresh()->expires_at->equalTo($recent->started_at->copy()->addMinutes(30)));
    }

    #[DataProvider('validDurations')]
    public function test_duration_components_are_saved_as_seconds_and_shown_on_edit(array $parts, int $seconds): void
    {
        [, $assessment] = $this->timerFixture(null);
        $this->actingAs($assessment->lesson->course->mentor);
        $payload = array_merge([
            'lesson_id' => $assessment->lesson_id, 'title' => $assessment->title,
            'passing_score' => 50, 'status' => 'Published', 'duration_enabled' => '1',
            'time_limit' => 999999,
        ], $parts);
        $this->put(route('assessments.update', $assessment), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($seconds, $assessment->fresh()->time_limit);
        $this->get(route('assessments.edit', $assessment))->assertOk()->assertSee('Assessment duration')
            ->assertSee('name="duration_seconds"', false)->assertSee('Changes apply to new attempts.');

        $payload['status'] = 'Draft';
        $payload['title'] = 'New timed assessment';
        $this->post(route('assessments.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('assessments', ['title' => 'New timed assessment', 'time_limit' => $seconds]);
    }

    public static function validDurations(): array
    {
        return [
            'ninety minutes' => [['duration_hours' => '01', 'duration_minutes' => '30', 'duration_seconds' => '00'], 5400],
            'thirty minutes' => [['duration_hours' => '00', 'duration_minutes' => '30', 'duration_seconds' => '00'], 1800],
            'five and a half minutes' => [['duration_hours' => '00', 'duration_minutes' => '05', 'duration_seconds' => '30'], 330],
            'ten seconds' => [['duration_hours' => 0, 'duration_minutes' => 0, 'duration_seconds' => 10], 10],
            'one second' => [['duration_hours' => 0, 'duration_minutes' => 0, 'duration_seconds' => 1], 1],
            'maximum' => [['duration_hours' => 10, 'duration_minutes' => 0, 'duration_seconds' => 0], 36000],
        ];
    }

    #[DataProvider('invalidDurations')]
    public function test_invalid_duration_is_rejected_on_create_and_update(array $parts, string $error): void
    {
        [, $assessment] = $this->timerFixture(600);
        $this->actingAs($assessment->lesson->course->mentor);
        $payload = array_merge([
            'lesson_id' => $assessment->lesson_id, 'title' => 'Invalid timed assessment',
            'passing_score' => 50, 'status' => 'Draft', 'duration_enabled' => '1',
            'duration_hours' => 0, 'duration_minutes' => 0, 'duration_seconds' => 0,
        ], $parts);
        $this->post(route('assessments.store'), $payload)->assertSessionHasErrors($error);
        $this->put(route('assessments.update', $assessment), $payload)->assertSessionHasErrors($error);
        $this->assertSame(600, $assessment->fresh()->time_limit);
        $this->assertDatabaseMissing('assessments', ['title' => 'Invalid timed assessment']);
    }

    public static function invalidDurations(): array
    {
        return [
            'zero' => [[], 'time_limit'],
            'over maximum' => [['duration_hours' => 10, 'duration_seconds' => 1], 'time_limit'],
            'sixty seconds' => [['duration_seconds' => 60], 'duration_seconds'],
            'sixty minutes' => [['duration_minutes' => 60], 'duration_minutes'],
            'negative' => [['duration_seconds' => -1], 'duration_seconds'],
            'decimal' => [['duration_seconds' => '1.5'], 'duration_seconds'],
            'array' => [['duration_seconds' => ['10']], 'duration_seconds'],
            'missing' => [['duration_seconds' => null], 'duration_seconds'],
        ];
    }

    public function test_disabling_duration_preserves_live_deadline_and_makes_future_attempts_untimed(): void
    {
        [$student, $assessment] = $this->timerFixture(600);
        $attempt = $this->begin($assessment);
        $this->actingAs($assessment->lesson->course->mentor);
        $this->put(route('assessments.update', $assessment), [
            'lesson_id' => $assessment->lesson_id, 'title' => $assessment->title,
            'passing_score' => 50, 'status' => 'Published', 'duration_enabled' => '0',
            'duration_hours' => '', 'duration_minutes' => '', 'duration_seconds' => '',
            'time_limit' => 99999,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNull($assessment->fresh()->time_limit);
        $this->assertTrue($attempt->fresh()->expires_at->equalTo($attempt->expires_at));
        $this->actingAs($student)->post(route('student.assessments.submit', $assessment), ['attempt_id' => $attempt->id])->assertRedirect();
        $this->assertNull($this->begin($assessment)->expires_at);
    }

    public function test_submitted_and_expired_attempts_share_the_same_limit(): void
    {
        $this->freezeTime();
        [, $assessment] = $this->timerFixture(10, 2);
        $submitted = $this->begin($assessment);
        $this->post(route('student.assessments.submit', $assessment), ['attempt_id' => $submitted->id])->assertRedirect();
        $expired = $this->begin($assessment);
        $this->travelTo($expired->expires_at);
        $this->post(route('student.assessments.start', $assessment))->assertRedirect(route('student.assessments.index'));
        $this->assertNotNull($expired->fresh()->expired_at);
        $this->assertSame(2, $assessment->attempts()->count());
    }

    public function test_submission_failure_rolls_back_answers_and_leaves_attempt_available(): void
    {
        [, $assessment] = $this->timerFixture(600);
        $this->makeQuestion($assessment, ['order_number' => 2]);
        $attempt = $this->begin($assessment);
        $saved = 0;
        StudentAnswer::creating(function () use (&$saved) {
            if (++$saved === 2) {
                throw new \RuntimeException('Simulated storage failure');
            }
        });
        try {
            $this->post(route('student.assessments.submit', $assessment), ['attempt_id' => $attempt->id])->assertStatus(500);
            $this->assertSame(0, $attempt->studentAnswers()->count());
            $this->assertTrue($attempt->fresh()->isActive());
        } finally {
            StudentAnswer::flushEventListeners();
        }
    }
}
