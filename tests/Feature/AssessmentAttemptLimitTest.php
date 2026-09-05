<?php

/**
 * @author OOI JIAN XIN
 *
 * Business rule: a student may never start more than max_attempts attempts on
 * an assessment. The displayed count must match the backend enforcement.
 *
 *  - 0/3, 1/3, 2/3 -> further attempts allowed
 *  - 3/3           -> no further attempts allowed, button disabled
 *  - the 4th attempt is blocked BEFORE a new attempt row is created
 *  - a direct HTTP request cannot bypass the limit
 *  - different students are independent
 *  - dropping and re-enrolling resets the attempt counter (fresh enrollment)
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AssessmentAttemptLimitTest extends TestCase
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
            'max_attempts' => 3,
            'status' => 'Published',
        ]);
    }

    protected function makeQuestion(Assessment $assessment): Question
    {
        return Question::create([
            'assessment_id' => $assessment->id,
            'question_text' => 'Question?',
            'question_type' => 'MCQ',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
            'marks' => 1,
            'order_number' => 1,
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

    protected function createAttempt(Enrollment $enrollment, Assessment $assessment, int $number, bool $submitted = true): AssessmentAttempt
    {
        return AssessmentAttempt::create([
            'enrollment_id' => $enrollment->id,
            'assessment_id' => $assessment->id,
            'attempt_number' => $number,
            'score' => 0,
            'percentage' => 0,
            'is_passed' => false,
            'started_at' => now(),
            'submitted_at' => $submitted ? now() : null,
        ]);
    }

    protected function summaryFor(Assessment $assessment, $summaries): object
    {
        $summary = collect($summaries)->first(
            fn ($s) => (int) $s->assessment->id === (int) $assessment->id
        );

        $this->assertNotNull($summary, 'Assessment summary missing from the index page.');

        return $summary;
    }

    public function test_attempt_counts_reflect_submitted_attempts_and_button_state(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $enrollment = $this->enroll($student, $course);
        $assessment = $this->makeAssessment($this->makeLesson($course));
        $this->makeQuestion($assessment);

        // ---- 0/3 ----
        $response = $this->actingAs($student)->get('/student/assessments');
        $summary = $this->summaryFor($assessment, $response->viewData('summaries'));
        $this->assertSame(0, $summary->attemptCount);
        $this->assertTrue($summary->canTake);
        $this->assertFalse($summary->hasInProgress);
        $response->assertSee('Start Assessment');

        // ---- 1/3 ----
        $this->createAttempt($enrollment, $assessment, 1);
        $response = $this->actingAs($student)->get('/student/assessments');
        $summary = $this->summaryFor($assessment, $response->viewData('summaries'));
        $this->assertSame(1, $summary->attemptCount);
        $this->assertTrue($summary->canTake);
        $response->assertSee('Retake Assessment');

        // ---- 2/3 ----
        $this->createAttempt($enrollment, $assessment, 2);
        $response = $this->actingAs($student)->get('/student/assessments');
        $summary = $this->summaryFor($assessment, $response->viewData('summaries'));
        $this->assertSame(2, $summary->attemptCount);
        $this->assertTrue($summary->canTake);

        // ---- 3/3 ----
        $this->createAttempt($enrollment, $assessment, 3);
        $response = $this->actingAs($student)->get('/student/assessments');
        $summary = $this->summaryFor($assessment, $response->viewData('summaries'));
        $this->assertSame(3, $summary->attemptCount);
        $this->assertFalse($summary->canTake);
        $response->assertSee('No attempts left');
    }

    public function test_fourth_attempt_is_blocked_before_creating_a_new_attempt(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $enrollment = $this->enroll($student, $course);
        $assessment = $this->makeAssessment($this->makeLesson($course));
        $this->makeQuestion($assessment);

        // Three submitted attempts are already on the record.
        foreach ([1, 2, 3] as $number) {
            $this->createAttempt($enrollment, $assessment, $number);
        }

        $this->assertSame(3, AssessmentAttempt::where('enrollment_id', $enrollment->id)->count());

        // Opening the take page must be blocked...
        $response = $this->actingAs($student)
            ->startAssessment($assessment)
            ->assertRedirect('/student/assessments')
            ->assertSessionHas('error');

        // ...and must NOT create attempt number 4.
        $attempts = AssessmentAttempt::where('enrollment_id', $enrollment->id)
            ->orderBy('attempt_number')
            ->get();

        $this->assertCount(3, $attempts);
        $this->assertSame([1, 2, 3], $attempts->pluck('attempt_number')->all());
    }

    public function test_direct_http_request_cannot_bypass_the_limit(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $enrollment = $this->enroll($student, $course);
        $assessment = $this->makeAssessment($this->makeLesson($course));
        $this->makeQuestion($assessment);

        // Max out the attempts directly in the database (bypassing the UI).
        for ($i = 1; $i <= 3; $i++) {
            $this->createAttempt($enrollment, $assessment, $i);
        }

        // A hand-crafted request to start attempt 4 is rejected.
        $this->actingAs($student)
            ->startAssessment($assessment)
            ->assertRedirect('/student/assessments');

        $this->assertSame(
            3,
            AssessmentAttempt::where('enrollment_id', $enrollment->id)->count()
        );
    }

    public function test_different_students_attempts_are_independent(): void
    {
        $studentA = $this->makeUser('Student', 'a@test.com');
        $studentB = $this->makeUser('Student', 'b@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $enrollmentA = $this->enroll($studentA, $course);
        $enrollmentB = $this->enroll($studentB, $course);
        $assessment = $this->makeAssessment($this->makeLesson($course));
        $this->makeQuestion($assessment);

        // Student A has exhausted their 3 attempts.
        foreach ([1, 2, 3] as $number) {
            $this->createAttempt($enrollmentA, $assessment, $number);
        }

        // Student A is blocked.
        $this->actingAs($studentA)
            ->startAssessment($assessment)
            ->assertRedirect('/student/assessments');

        // Student B, who has no attempts, may still start.
        $this->actingAs($studentB)
            ->startAssessment($assessment)
            ->assertOk();

        $this->assertSame(1, AssessmentAttempt::where('enrollment_id', $enrollmentB->id)->count());
    }

    public function test_dropped_and_reenrolled_course_starts_attempts_fresh(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $assessment = $this->makeAssessment($this->makeLesson($course));
        $this->makeQuestion($assessment);

        // First enrollment: exhaust all 3 attempts, then drop.
        $first = $this->enroll($student, $course);
        foreach ([1, 2, 3] as $number) {
            $this->createAttempt($first, $assessment, $number);
        }
        $this->actingAs($student)
            ->postJson('/student/courses/'.$course->slug.'/drop')
            ->assertOk();

        $this->assertSame('Dropped', $first->fresh()->status);

        // Re-enrol -> brand new enrollment row.
        $this->actingAs($student)
            ->postJson('/student/courses/'.$course->slug.'/enroll')
            ->assertOk();

        $second = Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', 'Active')
            ->firstOrFail();

        $this->assertNotEquals($first->id, $second->id);

        // The old cycle's attempts do not carry over: the student may start a
        // fresh attempt under the new enrollment (attempt_number 1).
        $this->actingAs($student)
            ->startAssessment($assessment)
            ->assertOk();

        $fresh = AssessmentAttempt::where('enrollment_id', $second->id)
            ->where('assessment_id', $assessment->id)
            ->first();

        $this->assertNotNull($fresh);
        $this->assertSame(1, $fresh->attempt_number);
        $this->assertSame(0, AssessmentAttempt::where('enrollment_id', $second->id)->where('submitted_at', '!=', null)->count());
    }

    public function test_in_progress_attempt_is_resumed_not_counted_as_a_new_attempt(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $enrollment = $this->enroll($student, $course);
        $assessment = $this->makeAssessment($this->makeLesson($course));
        $this->makeQuestion($assessment);

        // Two submitted attempts plus one in-progress attempt.
        $this->createAttempt($enrollment, $assessment, 1);
        $this->createAttempt($enrollment, $assessment, 2);
        $this->createAttempt($enrollment, $assessment, 3, submitted: false);

        // The index shows only submitted attempts as "used" and flags the
        // in-progress one for resuming.
        $response = $this->actingAs($student)->get('/student/assessments');
        $summary = $this->summaryFor($assessment, $response->viewData('summaries'));

        $this->assertSame(2, $summary->attemptCount);
        $this->assertTrue($summary->hasInProgress);
        $this->assertTrue($summary->canTake);
        $response->assertSee('Resume Assessment');

        // Opening the take page resumes the existing attempt - no 4th row.
        $this->actingAs($student)
            ->startAssessment($assessment)
            ->assertOk();

        $this->assertSame(3, AssessmentAttempt::where('enrollment_id', $enrollment->id)->count());
    }
}
