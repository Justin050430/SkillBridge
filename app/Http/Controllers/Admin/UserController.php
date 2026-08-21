<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Models\Role;
use App\Patterns\Command\Commands\NotifyAdminsOnSecurityEvent;
use App\Patterns\Command\NotificationInvoker;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request) {
        // Cap user-controlled search/filter input (values are used via
        // parameterised queries, but we still bound length/values).
        $request->validate([
            'search' => 'nullable|string|max:100',
            'role' => 'nullable|integer|exists:roles,id',
            'status' => 'nullable|in:Active,Inactive,Deleted,All',
        ]);

        $query = User::with('role');

        // The normal user listing shows Active users only. Admins can opt in
        // to view Inactive or Deleted users via the status filter.
        $status = $request->filled('status') ? $request->status : 'Active';

        if ($status !== 'All') {
            $query->where('status', $status);
        }

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('role', function ($role) use ($search) {

                            $role->where('role_name', 'like', "%{$search}%");
                        });
            });
        }
        
        if ($request->filled('role')) {

            $query->where('role_id', $request->role);
        }

        $users = $query
                ->orderBy('id')
                ->paginate(10)
                ->withQueryString();

        $roles = Role::orderBy('role_name')->get();

        return view('admin.users.index', compact('users', 'roles', 'status'));
    }

    public function create() {
        $roles = Role::orderBy('role_name')->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request) {
        $validated = $request->validated();

        User::create([
            'role_id' => $validated['role_id'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => 'Active',
        ]);

        return redirect()
                        ->route('admin.users')
                        ->with('success', 'User created successfully.');
    }
    
    public function edit(User $user) {
        $roles = Role::orderBy('role_name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user) {
        $validated = $request->validated();

        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->role_id = $validated['role_id'];

        // Allow admins to activate/deactivate a user without deleting them.
        if (isset($validated['status'])) {
            $user->status = $validated['status'];
        }

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
                        ->route('admin.users')
                        ->with('success', 'User updated successfully.');
    }
    
    /**
     * Deactivate an account: Active -> Inactive.
     *
     * This is a reversible status change (admins can reactivate the user via
     * the edit form). It is deliberately distinct from the "Delete" action,
     * which soft-deletes the account by marking it as Deleted.
     */
    public function deactivate(User $user) {
        // Deactivation only transitions an Active account to Inactive. It must
        // not resurrect a Deleted account or touch an already-Inactive one.
        if ($user->status !== 'Active') {

            return redirect()
                            ->route('admin.users')
                            ->with('info', 'Only Active accounts can be deactivated.');
        }

        // Prevent deactivating your own account (would lock you out).
        if ($user->id == auth()->id()) {

            return redirect()
                            ->route('admin.users')
                            ->with('error', 'You cannot deactivate your own account.');
        }

        // Prevent deactivating the last Active Admin.
        if (
                $user->role->role_name === 'Admin' && User::admins()->active()->count() === 1
        ) {

            return redirect()
                            ->route('admin.users')
                            ->with('warning', 'The last Active Admin cannot be deactivated.');
        }

        $user->update(['status' => 'Inactive']);

        return redirect()
                        ->route('admin.users')
                        ->with('success', 'User deactivated successfully. The account can no longer sign in.');
    }

    public function delete(User $user) {
        return view('admin.users.delete', compact('user'));
    }

    /**
     * Soft-delete a user by marking the account as Deleted.
     *
     * Distinct from deactivation: this is the irreversible-feeling removal for
     * accounts that are no longer needed, while the record, its IDs and all
     * historical relationships are preserved for data integrity/history.
     */
    public function destroy(User $user) {
        // Prevent deleting yourself
        if ($user->id == auth()->id()) {

            return redirect()
                            ->route('admin.users')
                            ->with('error', 'You cannot delete your own account.');
        }

        // Prevent deleting the last Active Admin (deleted admins no longer count).
        if (
                $user->role->role_name === 'Admin' && User::admins()->active()->count() === 1
        ) {

            return redirect()
                            ->route('admin.users')
                            ->with('warning', 'The last Admin cannot be deleted.');
        }

        // Status-based soft deletion: the record, its IDs and all historical
        // relationships are preserved, but the user can no longer authenticate
        // and no longer appears in the active user listing.
        $user->update(['status' => 'Deleted']);

        // Notify admins of this important account change (Command pattern).
        app(NotificationInvoker::class)->run(
            new NotifyAdminsOnSecurityEvent(
                app(NotificationService::class),
                'Account deactivated',
                "The account for {$user->first_name} {$user->last_name} ({$user->email}) has been deactivated.",
                User::class,
                $user->id
            )
        );

        return redirect()
                        ->route('admin.users')
                        ->with('success', 'User deleted successfully. The account has been deactivated.');
    }
}
