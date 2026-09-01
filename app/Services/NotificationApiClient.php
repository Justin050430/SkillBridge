<?php

/**
 * @author GEN TECK NG
 *
 * Consumes the Notification module's web service (POST /api/notifications),
 * following the IFA.
 *
 * This demonstrates bidirectional integration: the Enrollment Management
 * module requests notifications through the Notification module's web service
 * (instead of touching the notification table directly) when an enrollment is
 * completed or dropped.
 */

namespace App\Services;

use App\Models\User;
use App\Services\Concerns\InternalHttp;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class NotificationApiClient
{
    use InternalHttp;

    /**
     * Ask the Notification module to create a notification for a user.
     *
     * Returns true when the Notification module accepted the request
     * (HTTP success + IFA status "S"), false otherwise. Failures are logged
     * and swallowed so the caller's own workflow is never interrupted.
     */
    public function notify(
        int $userId,
        string $title,
        string $message,
        string $type,
        ?string $relatedType = null,
        ?int $relatedId = null
    ): bool {
        try {
            $response = $this->internalHttp()
                ->asJson()
                ->post(route('api.notifications.store'), [
                    'timestamp' => now()->toIso8601String(),
                    'requestId' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'title' => $title,
                    'message' => $message,
                    'notification_type' => $type,
                    'related_type' => $relatedType,
                    'related_id' => $relatedId,
                ]);

            $payload = $response->json();

            if (! $response->ok() || ($payload['status'] ?? '') !== 'S') {
                throw new \RuntimeException('Notification API returned a non-success status.');
            }

            return true;
        } catch (Throwable $exception) {
            Log::warning('Notification API consumption failed; falling back to local notification', [
                'timestamp' => now()->toIso8601String(),
                'endpoint' => 'api.notifications.store',
                'user_id' => $userId,
                'message' => $exception->getMessage(),
            ]);

            return $this->fallbackNotify($userId, $title, $message, $type, $relatedType, $relatedId);
        }
    }

    /**
     * Local fallback: creates the notification directly through the local
     * NotificationService, so the student still receives the notification even
     * when the Notification web service is unreachable (e.g. local dev server).
     */
    protected function fallbackNotify(
        int $userId,
        string $title,
        string $message,
        string $type,
        ?string $relatedType = null,
        ?int $relatedId = null
    ): bool {
        try {
            $user = User::find($userId);

            if (! $user) {
                return false;
            }

            app(NotificationService::class)->notify(
                $user,
                $title,
                $message,
                $type,
                $relatedType,
                $relatedId
            );

            return true;
        } catch (Throwable $exception) {
            Log::warning('Notification local fallback failed', [
                'user_id' => $userId,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
