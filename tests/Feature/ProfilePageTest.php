<?php

/**
 * @author OOI JIAN XIN
 *
 * The shared profile page must work correctly for every role - Student,
 * Mentor and Admin - showing at minimum the user's name, email, role and
 * account status, while preserving the existing editing capability.
 */

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(['role_name' => $roleName]);

        return User::create([
            'role_id' => $role->id,
            'first_name' => 'Test',
            'last_name' => $roleName,
            'email' => $email,
            'password' => Hash::make('password'),
            'status' => 'Active',
        ]);
    }

    public function test_student_profile_page_shows_account_information(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');

        $this->actingAs($student)
            ->get('/profile')
            ->assertOk()
            ->assertSee($student->first_name)
            ->assertSee($student->last_name)
            ->assertSee('student@test.com')
            ->assertSee('Student')
            ->assertSee('Active account')
            // Editing capability is preserved.
            ->assertSee('Profile Information')
            ->assertSee('Update Password');
    }

    public function test_mentor_profile_page_uses_admin_shell_and_shows_role(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');

        $this->actingAs($mentor)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Mentor')
            ->assertSee('Active account')
            ->assertSee('My Profile')
            // The mentor sees the sidebar shell, not the student top nav.
            ->assertSee('Assessment Management')
            ->assertDontSee('Browse Courses');
    }

    public function test_admin_profile_page_shows_account_information(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $this->actingAs($admin)
            ->get('/profile')
            ->assertOk()
            ->assertSee('admin@test.com')
            ->assertSee('Admin')
            ->assertSee('Active account');
    }

    public function test_profile_information_can_still_be_updated_from_shared_page(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');

        $this->actingAs($student)
            ->patch('/profile', [
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'email' => 'student@test.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('Updated', $student->fresh()->first_name);
        $this->assertSame('Name', $student->fresh()->last_name);
    }
}
