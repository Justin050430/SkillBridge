<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ApiErrorContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_api_request_returns_ifa_even_without_accept_header(): void
    {
        $response = $this->get('/api/users/1/profile?requestId=unauthenticated');
        $this->assertFailure($response, 401, 'unauthenticated');
    }

    public function test_missing_model_and_unknown_endpoint_return_request_failures(): void
    {
        $this->actingAs($this->user('Admin'));
        $this->assertFailure($this->getJson('/api/users/99999/profile?requestId=missing-user'), 404, 'missing-user');
        $this->assertFailure($this->getJson('/api/not-a-route?requestId=missing-route'), 404, 'missing-route');
    }

    public function test_forbidden_api_request_preserves_access_control(): void
    {
        $response = $this->actingAs($this->user('Student'))->postJson('/api/notifications', ['requestId' => 'forbidden']);
        $this->assertFailure($response, 403, 'forbidden');
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_validation_failure_retains_field_errors_and_trace_fields(): void
    {
        $response = $this->actingAs($this->user('Admin'))->postJson('/api/notifications', ['requestId' => 'invalid']);
        $this->assertFailure($response, 422, 'invalid');
        $response->assertJsonValidationErrors(['user_id', 'title', 'message', 'notification_type']);
    }

    public function test_rate_limit_failure_preserves_retry_header(): void
    {
        Route::get('/api/test-rate-limit', fn () => abort(429, 'internal details', ['Retry-After' => '17']));
        $response = $this->getJson('/api/test-rate-limit?requestId=rate-limit');
        $this->assertFailure($response, 429, 'rate-limit');
        $response->assertHeader('Retry-After', '17')->assertDontSee('internal details');
    }

    public function test_server_failure_is_sanitised_and_traceable(): void
    {
        Route::get('/api/test-error', fn () => throw new \RuntimeException('secret database credentials'));
        $response = $this->getJson('/api/test-error?requestId=server-error');
        $response->assertStatus(500)->assertJsonPath('status', 'E')->assertJsonPath('requestId', 'server-error');
        $response->assertJsonStructure(['timestamp', 'message'])->assertDontSee('secret database credentials');
    }

    public function test_error_without_request_id_generates_an_identifier(): void
    {
        $this->getJson('/api/not-a-route?timestamp=2026-09-06T00:00:00Z')
            ->assertNotFound()->assertJsonPath('status', 'F')
            ->assertJson(fn ($json) => $json->whereType('requestId', 'string')->whereType('timestamp', 'string')->etc());
    }

    public function test_browser_authentication_still_redirects_to_login(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
    }

    private function assertFailure(TestResponse $response, int $status, string $requestId): void
    {
        $response->assertStatus($status)->assertJsonPath('status', 'F')->assertJsonPath('requestId', $requestId)
            ->assertJsonStructure(['timestamp', 'message']);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role_id' => Role::create(['role_name' => $role])->id, 'status' => 'Active']);
    }
}
