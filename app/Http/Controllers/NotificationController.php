<?php

/**
 * @author YIK HANG YIP
 *
 * Notifications inbox shared by all roles (student, mentor, admin).
 * Ownership is enforced server-side: users can only ever see and mark their
 * own notifications.
 */

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->paginate(15);

        $user = Auth::user();
        $view = $user instanceof User && $user->isStudent()
            ? 'notifications.student'
            : 'notifications.index';

        return view($view, compact('notifications'));
    }

    /**
     * Mark a single notification as read (owner only).
     * Scoped route model binding ensures the notification belongs to the authenticated user.
     */
    public function markRead(Notification $notification)
    {
        // Route binding already ensures ownership, but we verify for safety
        abort_unless($notification?->user_id === Auth::id(), 404);

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', 'Notification marked as read.');
    }

    /**
     * Mark a single notification as unread (owner only).
     * Scoped route model binding ensures the notification belongs to the authenticated user.
     */
    public function markUnread(Notification $notification)
    {
        // Route binding already ensures ownership, but we verify for safety
        abort_unless($notification?->user_id === Auth::id(), 404);

        $notification->update([
            'is_read' => false,
            'read_at' => null,
        ]);

        return redirect()->back()
            ->with('success', 'Notification marked as unread.');
    }

    /**
     * Mark every notification of the current user as read.
     */
    public function markAllRead(Request $request)
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return redirect()->back()
            ->with('success', 'All notifications marked as read.');
    }
}
