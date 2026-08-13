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
     */
    public function markRead(Notification $notification)
    {
        abort_unless($notification->user_id === Auth::id(), 403, 'You can only manage your own notifications.');

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return redirect()->back();
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
