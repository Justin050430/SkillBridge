<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(
            ['role_name' => 'Admin'],
            ['description' => 'Full system access']
        );

        Role::updateOrCreate(
            ['role_name' => 'Mentor'],
            ['description' => 'Create and manage courses']
        );

        Role::updateOrCreate(
            ['role_name' => 'Student'],
            ['description' => 'Enroll in courses']
        );
    }
}