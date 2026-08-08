<?php

/**
 * @author OOI JIAN XIN
 *
 * Receiver for the GoF Command pattern. The NotificationService is the object
 * that concrete notification commands act upon - it is responsible for
 * actually creating the notification records. Commands carry the intent;
 * this service performs the work.
 */

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Create a notification record for the given user.
     */
    public function notify(User $user, string $title, string $message, string $type): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'notification_type' => $type,
        ]);
    }
}
