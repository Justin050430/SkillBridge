<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Patterns\Command\Commands\NotifyUserOnPasswordChange;
use App\Patterns\Command\NotificationInvoker;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // After a password change, drop every other active session for this
        // account (keep the current one) so other devices must sign in again.
        DB::table('sessions')
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        // Security notification to the affected user (Command pattern).
        app(NotificationInvoker::class)->run(
            new NotifyUserOnPasswordChange(
                app(NotificationService::class),
                $request->user()
            )
        );

        return back()->with('status', 'password-updated');
    }
}
