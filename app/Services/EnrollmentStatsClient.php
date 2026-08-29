<?php

/**
 * @author ZHAN ZHENG PUU
 *
 * Consumes the Enrollment Management module's web service
 * (GET /api/enrollments/user/{user}/stats), following the IFA.
 *
 * This demonstrates bidirectional integration: the User Management module
 * consumes the enrolment statistics exposed by the Enrollment module so an
 * administrator can see a user's enrolment activity on the user edit page.
 */

namespace App\Services;

use App\Services\Concerns\InternalHttp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class EnrollmentStatsClient
{
    use InternalHttp;

    /**
     * Fetch a user's enrolment statistics.
     *
     * Returns an array (user_id, total, active, completed, dropped) or null
     * when the service is unavailable or returns a non-success IFA status.
     */
    public function userStats(int $userId): ?array
    {
        try {
            $response = $this->internalHttp()->get(
                route('api.enrollments.user.stats', ['user' => $userId]),
                [
                    'timestamp' => now()->toIso8601String(),
                    'requestId' => (string) Str::uuid(),
                ]
            );

            $payload = $response->json();

            if (! $response->ok() || ($payload['status'] ?? '') !== 'S') {
                throw new \RuntimeException('Enrollment stats API returned a non-success status.');
            }

            return $payload['data'] ?? null;
        } catch (Throwable $exception) {
            Log::warning('Enrollment stats API consumption failed', [
                'timestamp' => now()->toIso8601String(),
                'endpoint' => 'api.enrollments.user.stats',
                'user_id' => $userId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
