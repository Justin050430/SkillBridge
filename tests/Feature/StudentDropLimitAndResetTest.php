<?php

/**
 * @author OOI JIAN XIN
 *
 * Business rules for the student enrolment flow:
 *  - Max 3 drops per course (derived from historical Dropped rows), enforced
 *    server-side so crafted requests cannot bypass it.
 *  - Dropping preserves historical attempts/progress (scoped by the old
 *    enrollment row) while a re-enrolment creates a FRESH row that starts at
 *    zero assessment attempts and zero lesson progress.
 *  - Ownership: students can only affect their own enrollments/progress.
 */

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Progress;
use App\Models\Question;
use App\Models\Role;
use App\Models\StudentAnswer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentDropLimitAndResetTest extends TestCase
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
            'max_attempts' => 3,
            'status' => 'Published',
        ], $overrides));
    }

    protected function makeQuestion(Assessment $assessment): Question
    {
        return Question::create([
            'assessment_id' => $assessment->id,
            'question_text' => 'What is 2 + 2?',
            'question_type' => 'MCQ',
            'option_a' => '4',
            'option_b' => '5',
            'option_c' => '6',
            'option_d' => '7',
            'correct_answer' => 'A',
            'marks' => 1,
            'order_number' => 1,
        ]);
    }

    protected function enroll(User $student, Course $course): void
    {
        $this->actingAs($student)
            ->postJson('/student/courses/'.$course->slug.'/enroll')
            ->assertOk();
    }

    protected function drop(User $student, Course $course): void
    {
        $this->actingAs($student)
            ->postJson('/student/courses/'.$course->slug.'/drop')
            ->assertOk();
    }

    protected function takeAndPassAssessment(User $student, Course $course, Assessment $assessment): void
    {
        // Opening the assessment creates an in-progress attempt under the
        // student's ACTIVE enrollment.
        $this->actingAs($student)
            ->get('/student/assessments/'.$assessment->slug.'/take')
            ->assertOk();

        // Submit the correct answer -> score 1/1 (100%), passed.
        $this->actingAs($student)
            ->post('/student/assessments/'.$assessment->slug.'/submit', [
                'answers' => [$assessment->questions()->first()->id => 'A'],
            ])
            ->assertRedirect();
    }

    protected function activeEnrollment(User $student, Course $course): ?Enrollment
    {
        return Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', 'Active')
            ->first();
    }

    public function test_catalogue_view_receives_progress_summary_for_active_enrollments(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $lesson1 = $this->makeLesson($course, 1);
        $lesson2 = $this->makeLesson($course, 2);
        $assessment = $this->makeAssessment($lesson2);
        $this->makeQuestion($assessment);

        $this->enroll($student, $course);

        // Lesson 1 completed manually; lesson 2 completed by passing its
        // assessment (ProgressUpdater marks the linked lesson Completed).
        $this->actingAs($student)
            ->post('/student/progress/'.$lesson1->id, ['status' => 'Completed'])
            ->assertRedirect();

        $this->takeAndPassAssessment($student, $course, $assessment);

        $this->actingAs($student)
            ->get('/student/courses')
            ->assertOk()
            ->assertViewHas('courseProgress', function ($courseProgress) use ($course) {
                $summary = $courseProgress->get($course->id);

                return $summary
                    && $summary['total_lessons'] === 2
                    && $summary['completed_lessons'] === 2
                    && $summary['percent'] === 100
                    && $summary['assessments_total'] === 1
                    && $summary['assessments_passed'] === 1;
            });
    }

    // ---- Test 1: full reset cycle ----

    public function test_reenrollment_starts_from_zero_progress(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course);
        $assessment = $this->makeAssessment($lesson);
        $this->makeQuestion($assessment);

        // First cycle: enroll, complete the assessment (passing marks the
        // lesson Completed in progress).
        $this->enroll($student, $course);
        $firstEnrollment = $this->activeEnrollment($student, $course);
        $this->takeAndPassAssessment($student, $course, $assessment);

        $this->assertSame(1, AssessmentAttempt::where('enrollment_id', $firstEnrollment->id)->count());
        $this->assertDatabaseHas('progress', [
            'enrollment_id' => $firstEnrollment->id,
            'lesson_id' => $lesson->id,
            'status' => 'Completed',
        ]);

        // Drop the course.
        $this->drop($student, $course);

        // Re-enrol -> a NEW enrollment row.
        $this->enroll($student, $course);
        $newEnrollment = $this->activeEnrollment($student, $course);

        $this->assertNotNull($newEnrollment);
        $this->assertNotEquals($firstEnrollment->id, $newEnrollment->id);

        // New enrollment starts from zero: no attempts, no progress.
        $this->assertSame(0, AssessmentAttempt::where('enrollment_id', $newEnrollment->id)->count());
        $this->assertSame(0, Progress::where('enrollment_id', $newEnrollment->id)->count());

        // Historical records are preserved under the old enrollment.
        $this->assertSame(1, AssessmentAttempt::where('enrollment_id', $firstEnrollment->id)->count());
        $this->assertDatabaseHas('progress', [
            'enrollment_id' => $firstEnrollment->id,
            'lesson_id' => $lesson->id,
            'status' => 'Completed',
        ]);

        // The assessment is available again as if starting fresh: a new take
        // is allowed (old cycle's max_attempts/attempts do not carry over)
        // and the fresh attempt belongs to the new enrollment.
        $this->actingAs($student)
            ->get('/student/assessments/'.$assessment->slug.'/take')
            ->assertOk();

        $freshAttempt = AssessmentAttempt::where('enrollment_id', $newEnrollment->id)
            ->where('assessment_id', $assessment->id)
            ->first();

        $this->assertNotNull($freshAttempt);
        $this->assertSame(1, $freshAttempt->attempt_number);
    }

    // ---- Tests 2-4: drop limit ----

    public function test_student_can_reenroll_after_one_drop(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->enroll($student, $course);
        $this->drop($student, $course);

        $this->enroll($student, $course);

        $this->assertNotNull($this->activeEnrollment($student, $course));
        $this->assertSame(2, Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->count());
    }

    public function test_student_can_reenroll_after_two_drops(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->enroll($student, $course);
        $this->drop($student, $course);
        $this->enroll($student, $course);
        $this->drop($student, $course);

        $this->enroll($student, $course);

        $this->assertNotNull($this->activeEnrollment($student, $course));
        $this->assertSame(3, Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->count());
    }

    public function test_student_cannot_reenroll_after_three_drops(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->enroll($student, $course);
        $this->drop($student, $course); // drop 1
        $this->enroll($student, $course);
        $this->drop($student, $course); // drop 2
        $this->enroll($student, $course);
        $this->drop($student, $course); // drop 3

        // 4th enrolment attempt is rejected by the backend.
        $this->actingAs($student)
            ->postJson('/student/courses/'.$course->slug.'/enroll')
            ->assertStatus(422)
            ->assertJson(['status' => 'E']);

        $this->assertNull($this->activeEnrollment($student, $course));
    }

    // ---- Test 5: direct request bypass ----

    public function test_backend_rejects_direct_request_when_limit_reached(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        // Seed three historical Dropped rows directly (as if the limit had
        // been reached through previous cycles).
        for ($i = 0; $i < 3; $i++) {
            Enrollment::create([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'status' => 'Dropped',
                'enrolled_at' => now(),
            ]);
        }

        // A hand-crafted HTTP request cannot bypass the limit.
        $this->actingAs($student)
            ->postJson('/student/courses/'.$course->slug.'/enroll')
            ->assertStatus(422)
            ->assertJson(['status' => 'E']);

        $this->assertNull($this->activeEnrollment($student, $course));
        $this->assertSame(3, Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->where('status', 'Dropped')->count());
    }

    // ---- Test 6: ownership ----

    public function test_student_cannot_drop_or_reset_another_students_enrollment(): void
    {
        $studentA = $this->makeUser('Student', 'a@test.com');
        $studentB = $this->makeUser('Student', 'b@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course);

        $this->enroll($studentA, $course);
        $enrollmentA = $this->activeEnrollment($studentA, $course);

        // A completes a lesson so A has active progress.
        $this->actingAs($studentA)
            ->post('/student/progress/'.$lesson->id, ['status' => 'Completed'])
            ->assertRedirect();

        // B tries to drop A's course -> 404 (B has no active enrollment there).
        $this->actingAs($studentB)
            ->postJson('/student/courses/'.$course->slug.'/drop')
            ->assertNotFound();

        // B tries to touch A's progress via the lesson -> 403 (not enrolled).
        $this->actingAs($studentB)
            ->post('/student/progress/'.$lesson->id, ['status' => 'Completed'])
            ->assertForbidden();

        // A's enrollment and progress are untouched.
        $this->assertSame('Active', $enrollmentA->fresh()->status);
        $this->assertDatabaseHas('progress', [
            'enrollment_id' => $enrollmentA->id,
            'lesson_id' => $lesson->id,
            'status' => 'Completed',
        ]);
    }

    // ---- Test 7: dropping one course leaves other courses alone ----

    public function test_dropping_one_course_does_not_reset_another_course(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');

        $courseA = $this->makeCourseFor($mentor);
        $courseB = $this->makeCourseFor($mentor);

        $lessonA = $this->makeLesson($courseA);
        $lessonB = $this->makeLesson($courseB);

        $this->enroll($student, $courseA);
        $this->enroll($student, $courseB);

        $enrollmentB = $this->activeEnrollment($student, $courseB);

        // Build progress in both courses.
        $this->actingAs($student)->post('/student/progress/'.$lessonA->id, ['status' => 'Completed']);
        $this->actingAs($student)->post('/student/progress/'.$lessonB->id, ['status' => 'Completed']);

        // Drop course A only.
        $this->drop($student, $courseA);

        // Course B is untouched.
        $this->assertSame('Active', $enrollmentB->fresh()->status);
        $this->assertDatabaseHas('progress', [
            'enrollment_id' => $enrollmentB->id,
            'lesson_id' => $lessonB->id,
            'status' => 'Completed',
        ]);
    }

    // ---- Test 8: dropping does not affect unrelated assessment records ----

    public function test_dropping_does_not_affect_unrelated_assessment_records(): void
    {
        $studentA = $this->makeUser('Student', 'a@test.com');
        $studentB = $this->makeUser('Student', 'b@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course);
        $assessment = $this->makeAssessment($lesson);
        $this->makeQuestion($assessment);

        // Both students are enrolled and have submitted attempts.
        $this->enroll($studentA, $course);
        $this->enroll($studentB, $course);
        $enrollmentA = $this->activeEnrollment($studentA, $course);
        $enrollmentB = $this->activeEnrollment($studentB, $course);

        $this->takeAndPassAssessment($studentA, $course, $assessment);
        $this->takeAndPassAssessment($studentB, $course, $assessment);

        $this->assertSame(1, AssessmentAttempt::where('enrollment_id', $enrollmentA->id)->count());
        $this->assertSame(1, AssessmentAttempt::where('enrollment_id', $enrollmentB->id)->count());

        // A drops the course.
        $this->drop($studentA, $course);

        // B's records are unaffected, and A's historical attempt remains.
        $this->assertSame(1, AssessmentAttempt::where('enrollment_id', $enrollmentB->id)->count());
        $this->assertSame(1, AssessmentAttempt::where('enrollment_id', $enrollmentA->id)->count());
        $this->assertSame(1, StudentAnswer::whereIn('attempt_id', AssessmentAttempt::where('enrollment_id', $enrollmentA->id)->pluck('id'))->count());

        // A's dropped enrollment is historical; B is still active.
        $this->assertSame('Dropped', $enrollmentA->fresh()->status);
        $this->assertSame('Active', $enrollmentB->fresh()->status);
    }
}
