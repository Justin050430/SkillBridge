<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request) {
        $query = User::with('role');

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

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create() {
        $roles = Role::orderBy('role_name')->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|confirmed|min:8',
        ]);

        User::create([
            'role_id' => $validated['role_id'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
                        ->route('admin.users')
                        ->with('success', 'User created successfully.');
    }
    
    public function edit(User $user) {
        $roles = Role::orderBy('role_name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user) {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|confirmed|min:8',
        ]);

        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->role_id = $validated['role_id'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
                        ->route('admin.users')
                        ->with('success', 'User updated successfully.');
    }
    
    public function delete(User $user) {
        return view('admin.users.delete', compact('user'));
    }

    public function destroy(User $user) {
        // Prevent deleting yourself
        if ($user->id == auth()->id()) {

            return redirect()
                            ->route('admin.users')
                            ->with('error', 'You cannot delete your own account.');
        }

        // Prevent deleting the last Admin
        if (
                $user->role->role_name === 'Admin' && User::admins()->count()
        ) {

            return redirect()
                            ->route('admin.users')
                            ->with('warning', 'The last Admin cannot be deleted.');
        }

        $user->delete();

        return redirect()
                        ->route('admin.users')
                        ->with('success', 'User deleted successfully.');
    }
}