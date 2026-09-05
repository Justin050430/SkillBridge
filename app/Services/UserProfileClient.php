<?php

/**
 * @author YIK HANG YIP
 *
 * Consumes the User Management module's web service
 * (GET /api/users/{user}/profile), following the IFA.
 *
 * This demonstrates bidirectional integration: the Notification module calls
 * the User module's profile service to validate a recipient and resolve their
 * display name before composing a notification.
 */

namespace App\Services;

use App\Models\User;
use App\Services\Concerns\InternalHttp;
use App\Support\ServiceExchangeLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class UserProfileClient
{
    use InternalHttp;

    /**
     * Fetch a user's public profile from the User Management module.
     *
     * Returns an array (user_id, first_name, last_name, role, status) or null
     * when the service is unavailable or returns a non-success IFA status.
     */
    public function profile(int $userId): ?array
    {
        $requestId = (string) Str::uuid();
        $response = null;
        try {
            $response = $this->internalHttp()->get(
                route('api.users.profile', ['user' => $userId]),
                [
                    'timestamp' => now()->toIso8601String(),
                    'requestId' => $requestId,
                ]
            );

            $payload = $response->json();

            if (! $response->ok() || ($payload['status'] ?? '') !== 'S') {
                throw new \RuntimeException('User profile API returned a non-success status.');
            }

            ServiceExchangeLog::record('api.users.profile', $requestId, $response, false);

            return $payload['data'] ?? null;
        } catch (Throwable $exception) {
            ServiceExchangeLog::record('api.users.profile', $requestId, $response, true);
            Log::warning('User profile API consumption failed; falling back to local lookup', [
                'timestamp' => now()->toIso8601String(),
                'endpoint' => 'api.users.profile',
                'user_id' => $userId,
                'message' => $exception->getMessage(),
            ]);

            return $this->fallbackProfile($userId);
        }
    }

    /**
     * Local fallback that reads the profile directly from the database, so the
     * Notification module can always resolve a recipient even when the User
     * web service is unreachable (e.g. local dev server).
     */
    protected function fallbackProfile(int $userId): ?array
    {
        try {
            $user = User::find($userId);

            if (! $user) {
                return null;
            }

            return [
                'user_id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role?->role_name,
                'status' => $user->status,
            ];
        } catch (Throwable $exception) {
            Log::warning('User profile local fallback failed', [
                'user_id' => $userId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
