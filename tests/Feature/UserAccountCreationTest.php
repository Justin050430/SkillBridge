<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Deliberately differ from seeded IDs to exercise role-name lookup.
        foreach ([41 => 'Admin', 73 => 'Mentor', 99 => 'Student'] as $id => $name) {
            $role = new Role(['role_name' => $name]);
            $role->id = $id;
            $role->save();
        }
    }

    public static function accountRoles(): array
    {
        return [['Student'], ['Mentor'], ['Admin']];
    }

    #[DataProvider('accountRoles')]
    public function test_admin_creates_active_accounts_with_the_selected_role_and_hashed_password(string $roleName): void
    {
        $admin = $this->makeAdmin();
        $role = Role::where('role_name', $roleName)->firstOrFail();

        $this->actingAs($admin)->post('/admin/users', [
            ...$this->accountData(),
            'role_id' => $role->id,
            'status' => 'Deleted',
        ])->assertSessionHasNoErrors()->assertRedirect('/admin/users');

        $user = User::where('email', 'created@example.com')->firstOrFail();
        $this->assertSame($roleName, $user->role->role_name);
        $this->assertSame($role->id, $user->role_id);
        $this->assertCreatedAccount($user);
        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_public_registration_always_creates_an_active_student(): void
    {
        $adminRole = Role::where('role_name', 'Admin')->firstOrFail();

        $this->post('/register', [
            ...$this->accountData(),
            'role_id' => $adminRole->id,
            'status' => 'Deleted',
        ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'created@example.com')->firstOrFail();
        $this->assertSame('Student', $user->role->role_name);
        $this->assertCreatedAccount($user);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_unsupported_role_is_rejected_without_creating_an_account(): void
    {
        $admin = $this->makeAdmin();
        $role = Role::create(['role_name' => 'Observer']);

        $this->actingAs($admin)->from('/admin/users/create')->post('/admin/users', [
            ...$this->accountData(),
            'role_id' => $role->id,
        ])->assertRedirect('/admin/users/create')->assertSessionHasErrors('role_id');

        $this->assertDatabaseMissing('users', ['email' => 'created@example.com']);
        $this->assertDatabaseCount('users', 1);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('role_name', 'Admin')->firstOrFail()->id,
            'status' => 'Active',
        ]);
    }

    private function accountData(): array
    {
        return [
            'first_name' => 'Created',
            'last_name' => 'Account',
            'email' => 'created@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ];
    }

    private function assertCreatedAccount(User $user): void
    {
        $this->assertSame('Created', $user->first_name);
        $this->assertSame('Account', $user->last_name);
        $this->assertSame('Active', $user->status);
        $this->assertTrue(Hash::check('Password@123', $user->getRawOriginal('password')));
        $this->assertNotSame('Password@123', $user->getRawOriginal('password'));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }
}
