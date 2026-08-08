<?php

/**
 * @author OOI JIAN XIN
 *
 * Consumes the Course module's web service (GET /api/courses/published),
 * following the IFA. This demonstrates bidirectional integration: the
 * Assessment & Progress module consumes a service exposed by the Course module.
 */

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CourseApiClient
{
    /**
     * Fetch the published course catalogue from the Course module API.
     *
     * Returns an array of course records (each with id, title, slug,
     * description, category, mentor) or an empty array if the service is
     * unavailable or returns a non-success IFA status.
     */
    public function publishedCourses(): array
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get(route('api.courses.published'));

            $payload = $response->json();

            if (!$response->ok() || ($payload['status'] ?? '') !== 'S') {
                throw new \RuntimeException('Course API returned a non-success status.');
            }

            return $payload['data'] ?? [];
        } catch (Throwable $exception) {
            Log::channel('assessment')->warning('Course API consumption failed', [
                'timestamp' => now()->toIso8601String(),
                'endpoint' => 'api.courses.published',
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }
}
