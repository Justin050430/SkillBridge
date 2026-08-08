<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Patterns\Observer\AssessmentSubmission;
use App\Patterns\Observer\Observers\ProgressUpdater;
use App\Patterns\Observer\Observers\SubmissionLogger;
use App\Services\CourseApiClient;
use Illuminate\Http\Request;
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
     * Only enrolled students may reach an assessment.
     */
    protected function ensureEnrolled(Assessment $assessment): void
    {
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
        $value = Enrollment::where('student_id', Auth::id())
            ->where('course_id', $assessment->lesson->course_id)
            ->where('status', 'Active')
            ->value('id');

        return $value === null ? null : (int) $value;
    }

    public function index()
    {
        $enrolledCourseIds = $this->enrolledCourseIds();

        $assessments = Assessment::with(['lesson.course'])
            ->withCount('questions')
            ->where('status', 'Published')
            ->whereHas('lesson', function ($lesson) use ($enrolledCourseIds) {
                $lesson->whereIn('course_id', $enrolledCourseIds);
            })
            ->orderBy('title')
            ->get();

        $attempts = AssessmentAttempt::with('assessment')
            ->whereHas('enrollment', function ($enrollment) {
                $enrollment->where('student_id', Auth::id());
            })
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

        abort_unless($assessment->status === 'Published', 404);

        if ($assessment->questions()->count() === 0) {
            return redirect()
                ->route('student.assessments.index')
                ->with('warning', 'This assessment has no questions yet.');
        }

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

    public function submit(Request $request, Assessment $assessment)
    {
        $this->ensureEnrolled($assessment);

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

        $submitted = $request->input('answers', []);
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
        // updating lesson progress and writing an audit log entry.
        $submission = new AssessmentSubmission($attempt, $assessment);
        $submission->attach(new ProgressUpdater());
        $submission->attach(new SubmissionLogger());
        $submission->notify();

        return redirect()
            ->route('student.assessments.result', $assessment);
    }

    public function result(Assessment $assessment)
    {
        $this->ensureEnrolled($assessment);

        $attempt = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->whereHas('enrollment', function ($enrollment) {
                $enrollment->where('student_id', Auth::id());
            })
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
