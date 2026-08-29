<?php

/**
 * @author YIK HANG YIP
 *
 * Web service EXPOSED by the Notification module.
 *
 * IFA (Interface Agreement):
 *   Request  : must carry a "timestamp" (or "requestId") field for tracking.
 *   Response : always carries "status" (S/F/E), "requestId" and "timestamp".
 *
 * POST /api/notifications
 *   Creates a notification for a recipient. Consumed by the Enrollment
 *   Management module (completion / drop notifications).
 *
 * This endpoint also CONSUMES the User Management module's profile web
 * service (GET /api/users/{user}/profile) to validate the recipient and
 * resolve their display name, demonstrating bidirectional integration between
 * the Notification and User modules. The notification itself is created via
 * the GoF Command pattern (NotificationInvoker + NotifyUser).
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Patterns\Command\Commands\NotifyUser;
use App\Patterns\Command\NotificationInvoker;
use App\Services\NotificationService;
use App\Services\UserProfileClient;
use App\Support\Ifa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class NotificationApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $requestId = Ifa::requestId($request);

        // IFA: requests must be traceable via timestamp and/or requestId.
        if ($missing = Ifa::assertTraceable($request)) {
            return $missing;
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
            'notification_type' => ['required', 'string', 'max:20'],
            'related_type' => ['nullable', 'string', 'max:191'],
            'related_id' => ['nullable', 'integer'],
        ]);

        try {
            $recipient = User::find((int) $validated['user_id']);

            if (! $recipient) {
                return Ifa::fail('The recipient does not exist.', 404, $requestId);
            }

            // CONSUME: the User module's profile service resolves the
            // recipient's display name (and confirms the user exists).
            // The profile client degrades gracefully; on success we surface
            // the resolved name in the response for tracking.
            $profile = app(UserProfileClient::class)->profile($recipient->id);
            $recipientName = is_array($profile) && isset($profile['first_name'])
                ? trim($profile['first_name'].' '.($profile['last_name'] ?? ''))
                : trim($recipient->first_name.' '.$recipient->last_name);

            // Command pattern: create the notification via the invoker.
            $command = new NotifyUser(
                app(NotificationService::class),
                $recipient,
                $validated['title'],
                $validated['message'],
                $validated['notification_type'],
                $validated['related_type'] ?? null,
                $validated['related_id'] ?? null
            );

            app(NotificationInvoker::class)->run($command);

            $notification = $command->getNotification();

            return Ifa::success([
                'notification_id' => $notification?->id,
                'user_id' => (int) $recipient->id,
                'recipient_name' => $recipientName,
            ], $requestId);
        } catch (Throwable $exception) {
            report($exception);

            return Ifa::error('The notification could not be created.', 500, $requestId);
        }
    }
}
