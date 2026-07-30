<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@skillbridge.com'],
            [
                'role_id' => Role::where('role_name', 'Admin')->value('id'),
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'password' => Hash::make('Admin@123'),
            ]
        );
    }
}