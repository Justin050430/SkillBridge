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
            Log::warning('Course analytics API consumption failed', [
                'timestamp' => now()->toIso8601String(),
                'endpoint' => 'api.assessments.course.stats',
                'course_id' => $courseId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
