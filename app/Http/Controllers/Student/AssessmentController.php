<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitAssessmentRequest;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Patterns\Observer\AssessmentSubmission;
use App\Patterns\Observer\Observers\NotificationDispatchObserver;
use App\Patterns\Observer\Observers\ProgressUpdater;
use App\Patterns\Observer\Observers\SubmissionLogger;
use App\Services\CourseApiClient;
use Illuminate\Support\Facades\Auth;

class AssessmentController extends Controller
{
    /**
     * Course IDs the current student is actively enrolled in.
     */
    protected function enrolledCourseIds(): array
    {
        return Enrollment::where('student_id', Auth::id())
            ->where('status', 'Active')
            ->pluck('course_id')
            ->all();
    }

    /**
     * Enrollment IDs of the student's current ACTIVE enrollments. Attempts and
     * progress are scoped by enrollment_id, so using only active enrollments
     * guarantees a re-enrolment after dropping starts from zero (a dropped
     * cycle's attempts never carry over).
     */
    protected function activeEnrollmentIds(): array
    {
        return Enrollment::where('student_id', Auth::id())
            ->where('status', 'Active')
            ->pluck('id')
            ->all();
    }

    /**
     * Only enrolled students may reach an assessment. An orphaned assessment
     * (missing parent lesson) is treated as not found rather than a 500.
     */
    protected function ensureEnrolled(Assessment $assessment): void
    {
        abort_unless($assessment->lesson !== null, 404);

        abort_unless(
            in_array($assessment->lesson->course_id, $this->enrolledCourseIds(), true),
            403,
            'You are not enrolled in this course.'
        );
    }

    /**
     * The student's active enrollment record for the assessment's course.
     */
    protected function enrollmentId(Assessment $assessment): ?int
    {
        if ($assessment->lesson === null) {
            return null;
        }

        $value = Enrollment::where('student_id', Auth::id())
            ->where('course_id', $assessment->lesson->course_id)
            ->where('status', 'Active')
            ->value('id');

        return $value === null ? null : (int) $value;
    }

    /**
     * A student may only access an assessment when it is Published, its parent
     * lesson is Published, AND it contains at least one question. Enforced
     * server-side so neither a draft lesson nor an empty assessment can ever be
     * bypassed via a direct URL.
     */
    protected function ensurePublishedForStudents(Assessment $assessment): void
    {
        abort_unless(
            $assessment->status === 'Published'
                && $assessment->lesson?->status === 'Published'
                && $assessment->questions()->exists(),
            404
        );
    }

    public function index()
    {
        $enrolledCourseIds = $this->enrolledCourseIds();

        $assessments = Assessment::with(['lesson.course'])
            ->withCount('questions')
            ->where('status', 'Published')
            ->has('questions')
            ->whereHas('lesson', function ($lesson) use ($enrolledCourseIds) {
                $lesson->whereIn('course_id', $enrolledCourseIds)
                    ->where('status', 'Published');
            })
            ->orderBy('title')
            ->get();

        $attempts = AssessmentAttempt::with('assessment')
            ->whereIn('enrollment_id', $this->activeEnrollmentIds())
            ->get()
            ->groupBy('assessment_id');

        $summaries = $assessments->map(function (Assessment $assessment) use ($attempts) {
            $studentAttempts = $attempts->get($assessment->id, collect());

            return (object) [
                'assessment' => $assessment,
                'attemptCount' => $studentAttempts->count(),
                'bestScore' => $studentAttempts->max('percentage'),
                'passed' => $studentAttempts->contains('is_passed', true),
                'canTake' => $this->canTake($assessment, $studentAttempts),
            ];
        });

        return view('student.assessments.index', compact('summaries'));
    }

    public function take(Assessment $assessment)
    {
        $this->ensureEnrolled($assessment);

        $this->ensurePublishedForStudents($assessment);

        $enrollmentId = $this->enrollmentId($assessment);

        // Resume an in-progress attempt if one exists.
        $attempt = AssessmentAttempt::where('enrollment_id', $enrollmentId)
            ->where('assessment_id', $assessment->id)
            ->whereNull('submitted_at')
            ->latest()
            ->first();

        if (!$attempt) {
            $completedCount = AssessmentAttempt::where('enrollment_id', $enrollmentId)
                ->where('assessment_id', $assessment->id)
                ->whereNotNull('submitted_at')
                ->count();

            if ($assessment->max_attempts !== null && $completedCount >= $assessment->max_attempts) {
                return redirect()
                    ->route('student.assessments.index')
                    ->with('error', 'You have used all your allowed attempts for this assessment.');
            }

            $attempt = AssessmentAttempt::create([
                'enrollment_id' => $enrollmentId,
                'assessment_id' => $assessment->id,
                'attempt_number' => $completedCount + 1,
                'score' => 0,
                'percentage' => 0,
                'is_passed' => false,
                'started_at' => now(),
            ]);
        }

        $questions = $assessment->questions()
            ->orderBy('order_number')
            ->get();

        return view('student.assessments.take', compact('assessment', 'questions', 'attempt'));
    }

    public function submit(SubmitAssessmentRequest $request, Assessment $assessment)
    {
        $this->ensureEnrolled($assessment);
        $this->ensurePublishedForStudents($assessment);

        $enrollmentId = $this->enrollmentId($assessment);

        $attempt = AssessmentAttempt::where('enrollment_id', $enrollmentId)
            ->where('assessment_id', $assessment->id)
            ->whereNull('submitted_at')
            ->latest()
            ->first();

        abort_unless($attempt, 403, 'No active attempt was found for this assessment.');

        $questions = $assessment->questions()
            ->orderBy('order_number')
            ->get();

        $submitted = $request->validated('answers') ?? [];
        $score = 0;
        $totalMarks = 0;

        foreach ($questions as $question) {
            $totalMarks += $question->marks;

            $raw = $submitted[$question->id] ?? null;
            $answer = is_string($raw) ? trim($raw) : '';

            $isCorrect = $this->isAnswerCorrect($question, $answer);

            if ($isCorrect) {
                $score += $question->marks;
            }

            StudentAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'submitted_answer' => $answer,
                'is_correct' => $isCorrect,
                'marks_awarded' => $isCorrect ? $question->marks : 0,
            ]);
        }

        $percentage = $totalMarks > 0 ? round(($score / $totalMarks) * 100, 2) : 0.0;

        $attempt->update([
            'score' => $score,
            'percentage' => $percentage,
            'is_passed' => $percentage >= $assessment->passing_score,
            'submitted_at' => now(),
        ]);

        // Publish the graded submission to registered observers
        // (GoF Observer pattern). Observers react to the state change, e.g.
        // updating lesson progress, writing an audit log entry, and
        // dispatching notification commands (Command pattern).
        $submission = new AssessmentSubmission($attempt, $assessment);
        $submission->attach(new ProgressUpdater());
        $submission->attach(new SubmissionLogger());
        $submission->attach(new NotificationDispatchObserver());
        $submission->notify();

        return redirect()
            ->route('student.assessments.result', $assessment);
    }

    public function result(Assessment $assessment)
    {
        $this->ensureEnrolled($assessment);
        $this->ensurePublishedForStudents($assessment);

        // Scope to the student's ACTIVE enrollment(s) only, so a submitted
        // attempt belonging to a previously DROPPED enrollment can never be
        // shown as the result of a fresh (re)enrollment.
        $attempt = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->whereIn('enrollment_id', $this->activeEnrollmentIds())
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->first();

        abort_unless($attempt, 404, 'No submitted result was found for this assessment.');

        $answers = $attempt->studentAnswers()
            ->with('question')
            ->get()
            ->keyBy('question_id');

        // Consume the Course module's web service to surface other published
        // courses on the result page (bidirectional web-service integration).
        $relatedCourses = app(CourseApiClient::class)->publishedCourses();

        return view('student.assessments.result', compact('assessment', 'attempt', 'answers', 'relatedCourses'));
    }

    /**
     * Whether the student is allowed to open another attempt.
     */
    protected function canTake(Assessment $assessment, $studentAttempts): bool
    {
        $hasInProgress = $studentAttempts->contains(function ($attempt) {
            return $attempt->submitted_at === null;
        });

        if ($hasInProgress || $assessment->max_attempts === null) {
            return true;
        }

        return $studentAttempts->count() < $assessment->max_attempts;
    }

    /**
     * Normalise the student's answer against the question's correct answer.
     */
    protected function isAnswerCorrect(Question $question, string $answer): bool
    {
        if ($answer === '') {
            return false;
        }

        $normalise = fn (string $value): string => strtolower(preg_replace('/\s+/', ' ', trim($value)) ?? '');

        return $normalise($question->correct_answer) === $normalise($answer);
    }
}
