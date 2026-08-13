<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Factories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserAccountFactory
{
    public function createStudent(array $data): User
    {
        return User::create([
            'role_id' => 3,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function createMentor(array $data): User
    {
        return User::create([
            'role_id' => 2,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function createAdmin(array $data): User
    {
        return User::create([
            'role_id' => 1,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }
}