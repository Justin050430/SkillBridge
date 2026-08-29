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

use App\Services\Concerns\InternalHttp;
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
        try {
            $response = $this->internalHttp()->get(
                route('api.users.profile', ['user' => $userId]),
                [
                    'timestamp' => now()->toIso8601String(),
                    'requestId' => (string) Str::uuid(),
                ]
            );

            $payload = $response->json();

            if (! $response->ok() || ($payload['status'] ?? '') !== 'S') {
                throw new \RuntimeException('User profile API returned a non-success status.');
            }

            return $payload['data'] ?? null;
        } catch (Throwable $exception) {
            Log::warning('User profile API consumption failed', [
                'timestamp' => now()->toIso8601String(),
                'endpoint' => 'api.users.profile',
                'user_id' => $userId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
