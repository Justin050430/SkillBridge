<?php

/**
 * @author PUAH ZHAN ZHENG
 */

namespace App\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserAccountFactory
{
    public function createStudent(array $data): User
    {
        return $this->createAccount($data, 'Student');
    }

    public function createMentor(array $data): User
    {
        return $this->createAccount($data, 'Mentor');
    }

    public function createAdmin(array $data): User
    {
        return $this->createAccount($data, 'Admin');
    }

    /**
     * Build an account using the database role that matches the requested
     * account type. Looking the role up by name avoids depending on fixed IDs.
     */
    protected function createAccount(array $data, string $roleName): User
    {
        $role = Role::query()
            ->where('role_name', $roleName)
            ->firstOrFail();

        return User::create([
            'role_id' => $role->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => 'Active',
        ]);
    }
}
