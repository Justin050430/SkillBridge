<?php

/**
 * @author ZHAN ZHENG PUU
 *
 * Verifies the User Management module's exposed web service:
 *   GET /api/users/{user}/profile (IFA-compliant)
 */

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserApiTest extends TestCase
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

    public function test_profile_returns_public_profile_with_ifa_fields(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $response = $this->actingAs($admin)
            ->getJson('/api/users/'.$student->id.'/profile?timestamp=2026-01-01T00:00:00Z');

        $response->assertOk();
        $response->assertJson([
            'status' => 'S',
            'requestId' => true,
            'timestamp' => true,
        ]);
        $response->assertJsonPath('data.user_id', $student->id);
        $response->assertJsonPath('data.first_name', 'Test');
        $response->assertJsonPath('data.last_name', 'Student');
        $response->assertJsonPath('data.role', 'Student');
        $response->assertJsonPath('data.status', 'Active');
    }

    public function test_profile_accepts_request_id_instead_of_timestamp(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $this->actingAs($admin)
            ->getJson('/api/users/'.$student->id.'/profile?requestId=req-abc-123')
            ->assertOk()
            ->assertJsonPath('status', 'S')
            ->assertJsonPath('requestId', 'req-abc-123');
    }

    public function test_profile_requires_timestamp_or_request_id(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $response = $this->actingAs($admin)
            ->getJson('/api/users/'.$student->id.'/profile');

        $response->assertStatus(422);
        $response->assertJsonPath('status', 'F');
        $response->assertJsonPath('message', 'A timestamp or requestId field is required.');
    }

    public function test_profile_requires_authentication(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');

        $this->getJson('/api/users/'.$student->id.'/profile?timestamp=2026-01-01T00:00:00Z')
            ->assertUnauthorized();
    }

    public function test_profile_returns_404_for_non_existent_user(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $this->actingAs($admin)
            ->getJson('/api/users/999999/profile?timestamp=2026-01-01T00:00:00Z')
            ->assertNotFound();
    }
}
