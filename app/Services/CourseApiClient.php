<?php

/**
 * @author OOI JIAN XIN
 *
 * Consumes the Course module's web service (GET /api/courses/published),
 * following the IFA. This demonstrates bidirectional integration: the
 * Assessment & Progress module consumes a service exposed by the Course module.
 */

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
            // IFA: every request carries a timestamp and/or requestId so the
            // course service can track it. The response's status/timestamp are
            // verified below before the data is trusted.
            $response = Http::timeout(5)
                ->acceptJson()
                ->get(route('api.courses.published'), [
                    'timestamp' => now()->toIso8601String(),
                    'requestId' => (string) Str::uuid(),
                ]);

            $payload = $response->json();

            if (!$response->ok() || ($payload['status'] ?? '') !== 'S') {
                throw new \RuntimeException('Course API returned a non-success status.');
            }

            return $payload['data'] ?? [];
        } catch (Throwable $exception) {
            Log::channel('assessment')->warning('Course API consumption failed; falling back to the local catalogue', [
                'timestamp' => now()->toIso8601String(),
                'endpoint' => 'api.courses.published',
                'message' => $exception->getMessage(),
            ]);

            return $this->fallbackPublishedCourses();
        }
    }

    /**
     * Local fallback for when the Course web service is unreachable (e.g. the
     * single-threaded local dev server cannot service a loopback HTTP call).
     * Returns the published catalogue from the database in the exact same
     * shape as the web service, so the consuming page always has data.
     */
    protected function fallbackPublishedCourses(): array
    {
        try {
            return Course::with(['mentor', 'category'])
                ->where('status', 'Published')
                ->orderBy('title')
                ->get()
                ->map(fn (Course $course) => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'slug' => $course->slug,
                    'description' => $course->description,
                    'thumbnail' => $course->thumbnail,
                    'category' => $course->category?->category_name,
                    'mentor' => $course->mentor
                        ? $course->mentor->first_name.' '.$course->mentor->last_name
                        : '',
                ])
                ->values()
                ->all();
        } catch (Throwable $exception) {
            Log::warning('Course catalogue local fallback failed', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }
}
