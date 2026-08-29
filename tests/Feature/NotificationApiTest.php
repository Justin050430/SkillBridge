<?php

/**
 * @author YIK HANG YIP
 *
 * Verifies the Notification module's exposed web service:
 *   POST /api/notifications (IFA-compliant)
 * Consumed by the Enrollment Management module. Also verifies that the
 * endpoint consumes the User module's profile service internally.
 */

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationApiTest extends TestCase
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

    protected function validPayload(int $userId): array
    {
        return [
            'timestamp' => now()->toIso8601String(),
            'requestId' => 'req-notif-1',
            'user_id' => $userId,
            'title' => 'Enrollment completed',
            'message' => 'Your enrollment has been completed.',
            'notification_type' => 'Enrollment',
            'related_type' => 'App\\Models\\Course',
            'related_id' => 1,
        ];
    }

    public function test_store_creates_notification_and_consumes_user_profile(): void
    {
        // The endpoint internally consumes the User module's profile service.
        Http::fake([
            '*/api/users/*/profile*' => Http::response([
                'status' => 'S',
                'requestId' => 'req-prof-1',
                'timestamp' => now()->toIso8601String(),
                'data' => [
                    'user_id' => 1,
                    'first_name' => 'Test',
                    'last_name' => 'Student',
                    'role' => 'Student',
                    'status' => 'Active',
                ],
            ], 200),
        ]);

        $admin = $this->makeUser('Admin', 'admin@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $payload = $this->validPayload($student->id);

        $response = $this->actingAs($admin)->postJson('/api/notifications', $payload);

        $response->assertOk();
        $response->assertJson([
            'status' => 'S',
            'requestId' => 'req-notif-1',
            'timestamp' => true,
        ]);
        $response->assertJsonPath('data.user_id', $student->id);
        $response->assertJsonPath('data.recipient_name', 'Test Student');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'title' => 'Enrollment completed',
            'notification_type' => 'Enrollment',
            'related_type' => 'App\\Models\\Course',
            'related_id' => 1,
        ]);

        // The endpoint must have consumed the User profile service.
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/users/') && str_contains($request->url(), '/profile');
        });
    }

    public function test_store_accepts_request_id_instead_of_timestamp(): void
    {
        Http::fake(['*/api/users/*/profile*' => Http::response(['status' => 'E'], 500)]);

        $admin = $this->makeUser('Admin', 'admin@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $payload = $this->validPayload($student->id);
        unset($payload['timestamp']);

        $this->actingAs($admin)
            ->postJson('/api/notifications', $payload)
            ->assertOk()
            ->assertJsonPath('status', 'S')
            ->assertJsonPath('requestId', 'req-notif-1');
    }

    public function test_store_requires_timestamp_or_request_id(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $payload = $this->validPayload($student->id);
        unset($payload['timestamp'], $payload['requestId']);

        $response = $this->actingAs($admin)->postJson('/api/notifications', $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('status', 'F');
        $response->assertJsonPath('message', 'A timestamp or requestId field is required.');
    }

    public function test_store_requires_authentication(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');

        $this->postJson('/api/notifications', $this->validPayload($student->id))
            ->assertUnauthorized();
    }

    public function test_store_blocks_students(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');

        $this->actingAs($student)
            ->postJson('/api/notifications', $this->validPayload($student->id))
            ->assertForbidden();
    }

    public function test_store_validates_required_fields(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $response = $this->actingAs($admin)->postJson('/api/notifications', [
            'timestamp' => now()->toIso8601String(),
            'requestId' => 'req-notif-2',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id', 'title', 'message', 'notification_type']);
    }

    public function test_store_returns_404_for_non_existent_recipient(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $payload = $this->validPayload(999999);

        $response = $this->actingAs($admin)->postJson('/api/notifications', $payload);

        $response->assertStatus(404);
        $response->assertJsonPath('status', 'F');
        $response->assertJsonPath('message', 'The recipient does not exist.');
    }
}
