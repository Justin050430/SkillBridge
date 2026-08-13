<?php

/**
 * @author OOI JIAN XIN
 *
 * Feature tests for the Admin Reports page: admin-only access, and the page
 * renders the aggregated analytics.
 */

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminReportsTest extends TestCase
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
        ]);
    }

    public function test_admin_can_view_reports_with_data(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $this->seed(DemoDataSeeder::class);

        $this->actingAs($admin)
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('Reports')
            ->assertSee('Courses by Category')
            ->assertSee('Top Courses by Enrollment')
            ->assertSee('Assessment Performance');
    }

    public function test_mentor_cannot_view_reports(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');

        $this->actingAs($mentor)
            ->get('/admin/reports')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/reports')
            ->assertRedirect('/login');
    }
}
