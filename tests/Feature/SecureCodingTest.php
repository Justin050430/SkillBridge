<?php

/**
 * @author OOI JIAN XIN
 *
 * Verifies the secure coding practices for the Assessment & Progress module:
 *  1. Access-control failures are logged (audit trail / non-repudiation).
 *  2. Error responses never disclose sensitive internal details
 *     (information-disclosure prevention).
 */

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\SecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class SecureCodingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Remove security log files left behind by previous runs/tests.
        array_map('unlink', glob(storage_path('logs/security-*.log')) ?: []);
    }

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

    protected function securityLogContents(): string
    {
        $content = '';

        foreach (glob(storage_path('logs/security-*.log')) ?: [] as $file) {
            $content .= file_get_contents($file);
        }

        return $content;
    }

    public function test_access_control_failure_is_logged(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');

        // A student is not allowed into the Admin/Mentor assessment area.
        $this->actingAs($student)
            ->get('/assessments')
            ->assertForbidden();

        $content = $this->securityLogContents();

        $this->assertStringContainsString('Access control failure', $content);
        $this->assertStringContainsString('assessments', $content);
        $this->assertStringContainsString((string) $student->id, $content);
        $this->assertStringContainsString('Student', $content);
    }

    public function test_api_error_response_does_not_leak_internal_details(): void
    {
        Route::get('/api/boom', function () {
            throw new RuntimeException('SECRET-INTERNAL-DETAIL-DB-CREDENTIAL');
        });

        $response = $this->getJson('/api/boom');

        $response->assertStatus(500);
        $response->assertJson(['status' => 'E']);
        $response->assertJsonPath('message', 'An unexpected error occurred. Please try again later.');

        $this->assertStringNotContainsString('SECRET-INTERNAL-DETAIL-DB-CREDENTIAL', $response->getContent());
        $this->assertStringNotContainsString('RuntimeException', $response->getContent());
    }

    public function test_safe_error_payload_contains_no_exception_details(): void
    {
        $payload = SecurityService::safeErrorPayload(
            new RuntimeException('SECRET-INTERNAL-DETAIL'),
            500
        );

        $this->assertEquals('E', $payload['status']);
        $this->assertArrayHasKey('timestamp', $payload);
        $this->assertArrayHasKey('message', $payload);
        $this->assertStringNotContainsString('SECRET-INTERNAL-DETAIL', json_encode($payload));
    }
}
