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
use App\Services\AssessmentAttemptService;
use App\Services\CourseApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

            $submitted = $studentAttempts->filter(fn ($attempt) => $attempt->submitted_at !== null);

            return (object) [
                'assessment' => $assessment,
                'attemptCount' => $studentAttempts->filter(fn ($attempt) => ! $attempt->isActive())->count(),
                'hasInProgress' => $studentAttempts->contains(fn ($attempt) => $attempt->isActive()),
                'bestScore' => $submitted->max('percentage'),
                'passed' => $submitted->contains('is_passed', true),
                'canTake' => $this->canTake($assessment, $studentAttempts),
            ];
        });

        return view('student.assessments.index', compact('summaries'));
    }

    public function start(Assessment $assessment, AssessmentAttemptService $attempts)
    {
        $this->ensureEnrolled($assessment);
        $this->ensurePublishedForStudents($assessment);
        $attempt = $attempts->start($assessment, $this->enrollmentId($assessment));

        if (! $attempt) {
            return redirect()->route('student.assessments.index')
                ->with('error', 'You have used all your allowed attempts for this assessment.');
        }

        return redirect()->route('student.assessments.take', ['assessment' => $assessment, 'attempt' => $attempt->id]);
    }

    public function take(Request $request, Assessment $assessment, AssessmentAttemptService $attempts)
    {
        $this->ensureEnrolled($assessment);
        $this->ensurePublishedForStudents($assessment);
        $request->validate(['attempt' => 'nullable|integer|min:1']);
        $enrollmentId = $this->enrollmentId($assessment);
        $attemptId = $request->integer('attempt');

        // Legacy links may resume an attempt, but GET never allocates a retry.
        if (! $attemptId) {
            $attemptId = AssessmentAttempt::where('enrollment_id', $enrollmentId)
                ->where('assessment_id', $assessment->id)->whereNull('submitted_at')
                ->whereNull('expired_at')->latest('attempt_number')->value('id');
            if (! $attemptId) {
                return redirect()->route('student.assessments.index')
                    ->with('error', 'Choose Start or Retake Assessment to begin an attempt.');
            }

            return redirect()->route('student.assessments.take', ['assessment' => $assessment, 'attempt' => $attemptId]);
        }

        return $attempts->withAttempt($assessment, $enrollmentId, $attemptId, function ($attempt) use ($assessment) {
            if (! $attempt->isActive()) {
                return $this->endedResponse($assessment, $attempt);
            }
            $questions = $assessment->questions()->orderBy('order_number')->get();

            return response()->view('student.assessments.take', compact('assessment', 'questions', 'attempt'))
                ->header('Cache-Control', 'no-store, private');
        });
    }

    public function expire(Request $request, Assessment $assessment, AssessmentAttemptService $attempts)
    {
        $this->ensureEnrolled($assessment);
        $this->ensurePublishedForStudents($assessment);
        $validated = $request->validate(['attempt_id' => 'required|integer|min:1']);

        return $attempts->withAttempt($assessment, $this->enrollmentId($assessment), (int) $validated['attempt_id'],
            function ($attempt) use ($assessment) {
                return response()->json([
                    'state' => $attempt->submitted_at ? 'submitted' : ($attempt->hasExpired() ? 'expired' : 'active'),
                    'server_now' => now()->getTimestampMs(),
                    'expires_at' => $attempt->expires_at?->getTimestampMs(),
                    'redirect_url' => route('student.assessments.result', ['assessment' => $assessment, 'attempt' => $attempt->id]),
                ])->header('Cache-Control', 'no-store, private');
            });
    }

    public function submit(SubmitAssessmentRequest $request, Assessment $assessment, AssessmentAttemptService $attempts)
    {
        $this->ensureEnrolled($assessment);
        $this->ensurePublishedForStudents($assessment);

        return $attempts->withAttempt($assessment, $this->enrollmentId($assessment), $request->integer('attempt_id'),
            function ($attempt) use ($request, $assessment) {
                if (! $attempt->isActive()) {
                    return $this->endedResponse($assessment, $attempt);
                }

                // Expiration is resolved before answer validation can redirect back.
                $submitted = $request->validatedAnswers();
                $acceptedAt = now();
                if ($attempt->expires_at !== null && $acceptedAt->greaterThanOrEqualTo($attempt->expires_at)) {
                    app(AssessmentAttemptService::class)->expireIfDue($attempt);

                    return $this->endedResponse($assessment, $attempt);
                }

                $questions = $assessment->questions()->orderBy('order_number')->get();
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
                    'submitted_at' => $acceptedAt,
                ]);

                // Run once, after the successful state transition is committed.
                DB::afterCommit(function () use ($attempt, $assessment) {
                    $submission = new AssessmentSubmission($attempt, $assessment);
                    $submission->attach(new ProgressUpdater);
                    $submission->attach(new SubmissionLogger);
                    $submission->attach(new NotificationDispatchObserver);
                    $submission->notify();
                });

                return redirect()->route('student.assessments.result', $assessment);
            });
    }

    private function endedResponse(Assessment $assessment, AssessmentAttempt $attempt)
    {
        return redirect()->route('student.assessments.result', ['assessment' => $assessment, 'attempt' => $attempt->id]);
    }

    public function result(Request $request, Assessment $assessment, AssessmentAttemptService $attempts)
    {
        $this->ensureEnrolled($assessment);
        $this->ensurePublishedForStudents($assessment);
        $request->validate(['attempt' => 'nullable|integer|min:1']);

        $query = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->whereIn('enrollment_id', $this->activeEnrollmentIds())->ended();
        if ($request->filled('attempt')) {
            $query->whereKey($request->integer('attempt'));
        }
        $attempt = $query->latest('attempt_number')->first();
        abort_unless($attempt, 404, 'No ended attempt was found for this assessment.');
        $attempts->expireIfDue($attempt);

        $answers = $attempt->studentAnswers()->with('question')->get()->keyBy('question_id');
        $studentAttempts = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('enrollment_id', $attempt->enrollment_id)->get();
        $canTake = $this->canTake($assessment, $studentAttempts);
        $hasInProgress = $studentAttempts->contains(fn ($item) => $item->isActive());
        $remainingAttempts = $assessment->max_attempts === null
            ? null : max(0, $assessment->max_attempts - $studentAttempts->count());
        $relatedCourses = $attempt->hasExpired() ? [] : app(CourseApiClient::class)->publishedCourses();

        return view('student.assessments.result', compact(
            'assessment', 'attempt', 'answers', 'relatedCourses', 'canTake', 'hasInProgress', 'remainingAttempts'
        ));
    }

    protected function canTake(Assessment $assessment, $studentAttempts): bool
    {
        return $studentAttempts->contains(fn ($attempt) => $attempt->isActive())
            || $assessment->max_attempts === null
            || $studentAttempts->count() < $assessment->max_attempts;
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
