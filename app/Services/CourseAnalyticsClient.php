<?php

/**
 * @author ZHI FOOK CHUA
 *
 * Consumes the Assessment & Progress module's web service
 * (GET /api/assessments/course/{course}/stats), following the IFA.
 *
 * This demonstrates bidirectional integration: the Course Management module
 * consumes the assessment analytics exposed by the Assessment module so a
 * course owner can see how the course's assessments are performing.
 */

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Services\Concerns\InternalHttp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CourseAnalyticsClient
{
    use InternalHttp;

    /**
     * Fetch aggregate assessment statistics for a course.
     *
     * Returns an array (course_id, course_title, assessment_count,
     * question_count, attempt_count, average_score, pass_rate) or null when
     * the service is unavailable or returns a non-success IFA status.
     */
    public function courseStats(int $courseId): ?array
    {
        try {
            $response = $this->internalHttp()->get(
                route('api.assessments.course.stats', ['course' => $courseId]),
                [
                    'timestamp' => now()->toIso8601String(),
                    'requestId' => (string) Str::uuid(),
                ]
            );

            $payload = $response->json();

            if (! $response->ok() || ($payload['status'] ?? '') !== 'S') {
                throw new \RuntimeException('Course analytics API returned a non-success status.');
            }

            return $payload['data'] ?? null;
        } catch (Throwable $exception) {
            Log::warning('Course analytics API consumption failed; falling back to local computation', [
                'timestamp' => now()->toIso8601String(),
                'endpoint' => 'api.assessments.course.stats',
                'course_id' => $courseId,
                'message' => $exception->getMessage(),
            ]);

            return $this->fallbackCourseStats($courseId);
        }
    }

    /**
     * Local fallback that computes the same aggregates directly from the
     * database, so the course edit page always shows real analytics even when
     * the Assessment web service is unreachable (e.g. local dev server).
     */
    protected function fallbackCourseStats(int $courseId): ?array
    {
        try {
            $course = Course::with('lessons.assessments')->find($courseId);

            if (! $course) {
                return null;
            }

            $assessmentIds = $course->lessons->flatMap->assessments->pluck('id');

            $attempts = AssessmentAttempt::whereIn('assessment_id', $assessmentIds)
                ->whereNotNull('submitted_at')
                ->get();

            $total = $attempts->count();

            $questionCount = Assessment::whereIn('id', $assessmentIds)
                ->withCount('questions')
                ->get()
                ->sum('questions_count');

            return [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'assessment_count' => $assessmentIds->count(),
                'question_count' => $questionCount,
                'attempt_count' => $total,
                'average_score' => $total > 0 ? round((float) $attempts->avg('percentage'), 2) : null,
                'pass_rate' => $total > 0 ? round($attempts->where('is_passed', true)->count() / $total * 100, 2) : null,
            ];
        } catch (Throwable $exception) {
            Log::warning('Course analytics local fallback failed', [
                'course_id' => $courseId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
