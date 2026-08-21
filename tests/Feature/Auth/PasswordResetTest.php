<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $email, string $password = 'old-password'): User
    {
        $role = Role::firstOrCreate(['role_name' => 'Student']);

        return User::create([
            'role_id' => $role->id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => Hash::make($password),
            'status' => 'Active',
        ]);
    }

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);

        // A reset token row is persisted for the address.
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_reset_password_link_request_handles_unregistered_email_safely(): void
    {
        Notification::fake();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertRedirect('/forgot-password');

        Notification::assertNothingSent();

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'nobody@example.com',
        ]);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_resetting_password_hashes_it_and_invalidates_old_password(): void
    {
        $user = $this->makeUser('reset@test.com');
        $token = app('auth.password.broker')->createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ])->assertRedirect('/login');

        // Hashed, not stored in plain text.
        $this->assertNotSame('NewPass@123', $user->fresh()->password);
        $this->assertTrue(Hash::check('NewPass@123', $user->fresh()->password));

        // Old password no longer works, new password does.
        $this->post('/login', ['email' => $user->email, 'password' => 'old-password'])
            ->assertSessionHasErrors('email');

        $this->post('/login', ['email' => $user->email, 'password' => 'NewPass@123'])
            ->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }

    public function test_password_confirmation_is_required(): void
    {
        $user = $this->makeUser('reset@test.com');
        $token = app('auth.password.broker')->createToken($user);

        $this->from('/reset-password/'.$token)
            ->post('/reset-password', [
                'token' => $token,
                'email' => $user->email,
                'password' => 'NewPass@123',
                'password_confirmation' => 'Different@123',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = $this->makeUser('reset@test.com');

        $this->from('/reset-password/bad-token')
            ->post('/reset-password', [
                'token' => 'bad-token',
                'email' => $user->email,
                'password' => 'NewPass@123',
                'password_confirmation' => 'NewPass@123',
            ])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = $this->makeUser('reset@test.com');
        $token = app('auth.password.broker')->createToken($user);

        // Expire the token (default expiry is 60 minutes).
        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subHours(2)]);

        $this->from('/reset-password/'.$token)
            ->post('/reset-password', [
                'token' => $token,
                'email' => $user->email,
                'password' => 'NewPass@123',
                'password_confirmation' => 'NewPass@123',
            ])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_reset_token_cannot_be_reused(): void
    {
        $user = $this->makeUser('reset@test.com');
        $token = app('auth.password.broker')->createToken($user);

        // First use succeeds.
        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ])->assertRedirect('/login');

        // Reusing the same token is rejected.
        $this->from('/reset-password/'.$token)
            ->post('/reset-password', [
                'token' => $token,
                'email' => $user->email,
                'password' => 'Another@123',
                'password_confirmation' => 'Another@123',
            ])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('NewPass@123', $user->fresh()->password));
    }
}
