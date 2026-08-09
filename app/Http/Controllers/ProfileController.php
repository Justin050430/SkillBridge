<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Patterns\Command\Commands\NotifyAdminsOnSecurityEvent;
use App\Patterns\Command\NotificationInvoker;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     *
     * Uses status-based soft deletion so the record and its historical
     * relationships are preserved, but the account can no longer sign in.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->update(['status' => 'Deleted']);

        // Notify admins that the user removed their own account.
        app(NotificationInvoker::class)->run(
            new NotifyAdminsOnSecurityEvent(
                app(NotificationService::class),
                'Account deactivated',
                "The account for {$user->first_name} {$user->last_name} ({$user->email}) was deactivated by the user.",
                User::class,
                $user->id
            )
        );

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
