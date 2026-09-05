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

use App\Models\Enrollment;
use App\Models\User;
use App\Services\Concerns\InternalHttp;
use App\Support\ServiceExchangeLog;
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
        $requestId = (string) Str::uuid();
        $response = null;
        try {
            $response = $this->internalHttp()->get(
                route('api.enrollments.user.stats', ['user' => $userId]),
                [
                    'timestamp' => now()->toIso8601String(),
                    'requestId' => $requestId,
                ]
            );

            $payload = $response->json();

            if (! $response->ok() || ($payload['status'] ?? '') !== 'S') {
                throw new \RuntimeException('Enrollment stats API returned a non-success status.');
            }

            ServiceExchangeLog::record('api.enrollments.user.stats', $requestId, $response, false);

            return $payload['data'] ?? null;
        } catch (Throwable $exception) {
            ServiceExchangeLog::record('api.enrollments.user.stats', $requestId, $response, true);
            Log::warning('Enrollment stats API consumption failed; falling back to local computation', [
                'timestamp' => now()->toIso8601String(),
                'endpoint' => 'api.enrollments.user.stats',
                'user_id' => $userId,
                'message' => $exception->getMessage(),
            ]);

            return $this->fallbackUserStats($userId);
        }
    }

    /**
     * Local fallback that computes the same counts directly from the database,
     * so the user edit page always shows real enrolment statistics even when
     * the Enrollment web service is unreachable (e.g. local dev server).
     */
    protected function fallbackUserStats(int $userId): ?array
    {
        try {
            if (! User::whereKey($userId)->exists()) {
                return null;
            }

            $enrollments = Enrollment::where('student_id', $userId)->get();

            return [
                'user_id' => $userId,
                'total' => $enrollments->count(),
                'active' => $enrollments->where('status', 'Active')->count(),
                'completed' => $enrollments->where('status', 'Completed')->count(),
                'dropped' => $enrollments->where('status', 'Dropped')->count(),
            ];
        } catch (Throwable $exception) {
            Log::warning('Enrollment stats local fallback failed', [
                'user_id' => $userId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
