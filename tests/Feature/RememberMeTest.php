<?php

/**
 * @author OOI JIAN XIN
 *
 * Verifies Laravel's built-in "remember me" authentication actually works:
 *  - unchecked -> no remember cookie / token is created
 *  - checked   -> a remember cookie + user remember_token are created
 *  - the remember cookie can restore authentication after the session ends
 *  - logging out invalidates the remember token so a stale cookie cannot
 *    silently restore the session
 */

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RememberMeTest extends TestCase
{
    use RefreshDatabase;

    protected function recallerName(): string
    {
        return 'remember_web_'.sha1(SessionGuard::class);
    }

    protected function makeUser(): User
    {
        $role = Role::firstOrCreate(['role_name' => 'Student']);

        return User::create([
            'role_id' => $role->id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'remember@test.com',
            'password' => Hash::make('password'),
            'status' => 'Active',
        ]);
    }

    public function test_login_without_remember_sets_no_remember_cookie(): void
    {
        $this->makeUser();

        $response = $this->post('/login', [
            'email' => 'remember@test.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasNoErrors();

        $cookies = collect($response->headers->getCookies());

        $this->assertFalse(
            $cookies->contains(fn ($cookie) => $cookie->getName() === $this->recallerName())
        );

        // No remember token is persisted on the user record.
        $this->assertNull(
            User::where('email', 'remember@test.com')->first()->remember_token
        );
    }

    public function test_login_with_remember_sets_remember_cookie_and_token(): void
    {
        $this->makeUser();

        $response = $this->post('/login', [
            'email' => 'remember@test.com',
            'password' => 'password',
            'remember' => '1',
        ]);

        $response->assertSessionHasNoErrors();

        $cookies = collect($response->headers->getCookies());

        $this->assertTrue(
            $cookies->contains(fn ($cookie) => $cookie->getName() === $this->recallerName())
        );

        // A remember token is persisted on the user record.
        $this->assertNotNull(
            User::where('email', 'remember@test.com')->first()->remember_token
        );
    }

    public function test_remember_cookie_restores_authentication_after_session_ends(): void
    {
        $user = $this->makeUser();

        $response = $this->post('/login', [
            'email' => 'remember@test.com',
            'password' => 'password',
            'remember' => '1',
        ]);

        $rememberCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === $this->recallerName());

        $this->assertNotNull($rememberCookie);

        // Simulate a new browser session where only the remember cookie
        // survives (the session cookie is gone). The guard must restore the
        // user via the remember token.
        $this->withCookie($this->recallerName(), $rememberCookie->getValue())
            ->get('/dashboard')
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_invalidates_remember_cookie(): void
    {
        $this->makeUser();

        $response = $this->post('/login', [
            'email' => 'remember@test.com',
            'password' => 'password',
            'remember' => '1',
        ]);

        $rememberCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === $this->recallerName());

        $this->assertNotNull($rememberCookie);

        // Log out: the remember token is regenerated, so the old cookie can
        // no longer restore the session.
        $this->post('/logout');

        $this->withCookie($this->recallerName(), $rememberCookie->getValue())
            ->get('/dashboard')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
