<?php

/**
 * @author OOI JIAN XIN
 *
 * Feature tests for status-based soft deletion of users.
 *  - Admin delete sets status=Deleted instead of removing the row.
 *  - Deleted/Inactive users cannot authenticate.
 *  - The user listing defaults to Active and can be filtered by status.
 *  - The last Active Admin cannot be deleted.
 */

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $roleName, string $email, string $status = 'Active'): User
    {
        $role = Role::firstOrCreate(['role_name' => $roleName]);

        return User::create([
            'role_id' => $role->id,
            'first_name' => 'Test',
            'last_name' => $roleName,
            'email' => $email,
            'password' => Hash::make('password'),
            'status' => $status,
        ]);
    }

    // ---- Soft deletion behaviour ----

    public function test_admin_delete_soft_deletes_instead_of_removing_record(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $this->actingAs($admin)
            ->delete('/admin/users/'.$student->id)
            ->assertRedirect('/admin/users');

        // The record still exists but is marked as Deleted.
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'email' => 'student@test.com',
            'status' => 'Deleted',
        ]);
    }

    public function test_deleted_users_do_not_appear_in_default_listing(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $active = $this->makeUser('Student', 'active@test.com', 'Active');
        $deleted = $this->makeUser('Student', 'deleted@test.com', 'Deleted');

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('active@test.com')
            ->assertDontSee('deleted@test.com');
    }

    public function test_deleted_users_appear_when_filtering_by_deleted_status(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $deleted = $this->makeUser('Student', 'deleted@test.com', 'Deleted');

        $this->actingAs($admin)
            ->get('/admin/users?status=Deleted')
            ->assertOk()
            ->assertSee('deleted@test.com');
    }

    // ---- Authentication restrictions ----

    public function test_deleted_user_cannot_authenticate(): void
    {
        $this->makeUser('Student', 'deleted@test.com', 'Deleted');

        $this->post('/login', [
            'email' => 'deleted@test.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $this->makeUser('Student', 'inactive@test.com', 'Inactive');

        $this->post('/login', [
            'email' => 'inactive@test.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_active_user_can_authenticate(): void
    {
        $this->makeUser('Student', 'active@test.com', 'Active');

        $this->post('/login', [
            'email' => 'active@test.com',
            'password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }

    // ---- Guard rails preserved ----

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $this->actingAs($admin)
            ->delete('/admin/users/'.$admin->id)
            ->assertRedirect('/admin/users');

        $this->assertSame('Active', $admin->fresh()->status);
    }

    public function test_last_active_admin_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        // Only one Active admin exists; soft-deleting it must be blocked.
        $this->actingAs($admin)
            ->delete('/admin/users/'.$admin->id)
            ->assertRedirect('/admin/users');

        $this->assertSame('Active', $admin->fresh()->status);
    }

    public function test_admin_can_soft_delete_when_another_active_admin_exists(): void
    {
        $adminA = $this->makeUser('Admin', 'adminA@test.com');
        $adminB = $this->makeUser('Admin', 'adminB@test.com');

        $this->actingAs($adminA)
            ->delete('/admin/users/'.$adminB->id)
            ->assertRedirect('/admin/users');

        $this->assertSame('Deleted', $adminB->fresh()->status);
    }

    // ---- Status toggling via edit ----

    public function test_admin_can_deactivate_user_via_update(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $this->actingAs($admin)
            ->put('/admin/users/'.$student->id, [
                'first_name' => 'Test',
                'last_name' => 'Student',
                'email' => 'student@test.com',
                'role_id' => $student->role_id,
                'status' => 'Inactive',
            ])
            ->assertRedirect('/admin/users');

        $this->assertSame('Inactive', $student->fresh()->status);
    }

    // ---- Dedicated deactivate action (Active -> Inactive) ----

    public function test_admin_can_deactivate_active_user_to_inactive(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $this->actingAs($admin)
            ->post('/admin/users/'.$student->id.'/deactivate')
            ->assertRedirect('/admin/users');

        $this->assertSame('Inactive', $student->fresh()->status);

        // Clear the guard so the login request is not treated as an
        // already-authenticated user (actingAs caches the user in the guard).
        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('auth.driver');

        // The inactive user can no longer sign in.
        $this->post('/login', [
            'email' => 'student@test.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $this->actingAs($admin)
            ->post('/admin/users/'.$admin->id.'/deactivate')
            ->assertRedirect('/admin/users');

        $this->assertSame('Active', $admin->fresh()->status);
    }

    public function test_last_active_admin_cannot_be_deactivated(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $this->actingAs($admin)
            ->post('/admin/users/'.$admin->id.'/deactivate')
            ->assertRedirect('/admin/users');

        $this->assertSame('Active', $admin->fresh()->status);
    }

    public function test_deactivating_a_deleted_user_does_not_resurrect_it(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $deleted = $this->makeUser('Student', 'deleted@test.com', 'Deleted');

        // Direct request cannot turn a Deleted account into Inactive.
        $this->actingAs($admin)
            ->post('/admin/users/'.$deleted->id.'/deactivate')
            ->assertRedirect('/admin/users');

        $this->assertSame('Deleted', $deleted->fresh()->status);
    }

    public function test_reactivating_an_inactive_user_works(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $student = $this->makeUser('Student', 'student@test.com', 'Inactive');

        $this->actingAs($admin)
            ->put('/admin/users/'.$student->id, [
                'first_name' => 'Test',
                'last_name' => 'Student',
                'email' => 'student@test.com',
                'role_id' => $student->role_id,
                'status' => 'Active',
            ])
            ->assertRedirect('/admin/users');

        $this->assertSame('Active', $student->fresh()->status);

        // Clear the guard so the login request is not treated as an
        // already-authenticated user (actingAs caches the user in the guard).
        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('auth.driver');

        // The reactivated user can sign in again.
        $this->post('/login', [
            'email' => 'student@test.com',
            'password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }
}
