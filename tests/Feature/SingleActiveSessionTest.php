<?php

/**
 * @author OOI JIAN XIN
 *
 * Business rule: each account may be logged in from only ONE browser/session
 * at a time. A new login invalidates the previous session. Enforcement is
 * server-side (database-backed sessions table), so it works across browsers,
 * incognito windows and devices, and cannot be bypassed with a different
 * browser or a remember-me cookie.
 *
 * These tests switch the session driver to 'database' (where the enforcement
 * lives). Each simulated browser request uses a fresh auth guard - exactly
 * like separate HTTP requests in a real web server - via freshRequest().
 * Cookies captured from a login response are replayed with
 * withUnencryptedCookies() because the test client would otherwise encrypt
 * them a second time.
 */

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class SingleActiveSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.driver' => 'database']);
    }

    /**
     * Simulate a brand-new HTTP request. In a real web server every request
     * is a separate process with an empty guard and an empty session, but in
     * feature tests the auth guard caches the previous request's user, the
     * session store keeps its in-memory attributes (Store::loadSession merges
     * rather than replaces), and the auth.driver singleton (aliased to the
     * Guard contract) caches the first resolved guard. We reset all three so
     * each simulated request starts from the same state a real request would.
     */
    protected function freshRequest(): void
    {
        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('auth.driver');
        $this->app['session.store']->flush();
    }

    protected function makeUser(string $email, string $roleName = 'Student'): User
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

    protected function sessionCookieName(): string
    {
        return config('session.cookie');
    }

    protected function rememberCookieName(): string
    {
        return 'remember_web_'.sha1(SessionGuard::class);
    }

    /**
     * The encrypted session/remember cookie value sent by a login response.
     * Replayed with withUnencryptedCookies().
     */
    protected function cookieValue(TestResponse $response, string $name): string
    {
        $cookie = collect($response->headers->getCookies())
            ->first(fn (Cookie $c) => $c->getName() === $name);

        $this->assertNotNull($cookie, "Cookie [{$name}] missing from the response.");

        return $cookie->getValue();
    }

    /**
     * The plain session id currently stored in the database for the user.
     */
    protected function activeSessionId(User $user): string
    {
        $id = DB::table('sessions')->where('user_id', $user->id)->value('id');

        $this->assertNotNull($id, 'No session row exists for the user.');

        return (string) $id;
    }

    protected function login(User $user, bool $remember = false): TestResponse
    {
        return $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => $remember ? '1' : '0',
        ]);
    }

    public function test_first_login_creates_an_active_session(): void
    {
        $user = $this->makeUser('first@test.com');

        $this->freshRequest();

        $this->login($user)->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);

        // Exactly one active session row exists for the account.
        $this->assertSame(1, DB::table('sessions')->where('user_id', $user->id)->count());
    }

    public function test_second_login_invalidates_the_first_session(): void
    {
        $user = $this->makeUser('second@test.com');

        $this->freshRequest();
        $first = $this->login($user);
        $firstSessionCookie = $this->cookieValue($first, $this->sessionCookieName());
        $firstSessionId = $this->activeSessionId($user);

        $this->freshRequest();
        $this->login($user);
        $secondSessionId = $this->activeSessionId($user);

        // The new login is authenticated and is a DIFFERENT session...
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($firstSessionId, $secondSessionId);
        $this->assertSame(1, DB::table('sessions')->where('user_id', $user->id)->count());

        // ...and the FIRST browser's cookie no longer works: its session row
        // was deleted, so a request carrying it is treated as a guest.
        $this->freshRequest();
        $this->withUnencryptedCookies([$this->sessionCookieName() => $firstSessionCookie])
            ->get('/dashboard')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_second_login_remains_authenticated(): void
    {
        $user = $this->makeUser('third@test.com');

        $this->freshRequest();
        $this->login($user);

        $this->freshRequest();
        $second = $this->login($user);
        $secondSessionCookie = $this->cookieValue($second, $this->sessionCookieName());

        // The newest session still works.
        $this->freshRequest();
        $this->withUnencryptedCookies([$this->sessionCookieName() => $secondSessionCookie])
            ->get('/dashboard')
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_different_accounts_are_unaffected(): void
    {
        $userA = $this->makeUser('a@test.com');
        $userB = $this->makeUser('b@test.com');

        $this->freshRequest();
        $loginA = $this->login($userA);
        $sessionACookie = $this->cookieValue($loginA, $this->sessionCookieName());

        $this->freshRequest();
        $loginB = $this->login($userB);
        $sessionBCookie = $this->cookieValue($loginB, $this->sessionCookieName());

        // Each account keeps its own active session.
        $this->assertSame(1, DB::table('sessions')->where('user_id', $userA->id)->count());
        $this->assertSame(1, DB::table('sessions')->where('user_id', $userB->id)->count());

        // Account A's session still works for A...
        $this->freshRequest();
        $this->withUnencryptedCookies([$this->sessionCookieName() => $sessionACookie])
            ->get('/dashboard')
            ->assertRedirect();
        $this->assertAuthenticatedAs($userA);

        // ...and B's session still works for B.
        $this->freshRequest();
        $this->withUnencryptedCookies([$this->sessionCookieName() => $sessionBCookie])
            ->get('/dashboard')
            ->assertRedirect();
        $this->assertAuthenticatedAs($userB);
    }

    public function test_same_browser_multiple_tabs_share_one_session(): void
    {
        $user = $this->makeUser('tabs@test.com');

        $this->freshRequest();
        $response = $this->login($user);
        $sessionCookie = $this->cookieValue($response, $this->sessionCookieName());

        // Two "tabs" = two requests with the SAME session cookie.
        $this->freshRequest();
        $this->withUnencryptedCookies([$this->sessionCookieName() => $sessionCookie])
            ->get('/dashboard')
            ->assertRedirect();
        $this->assertAuthenticatedAs($user);

        $this->freshRequest();
        $this->withUnencryptedCookies([$this->sessionCookieName() => $sessionCookie])
            ->get('/dashboard')
            ->assertRedirect();
        $this->assertAuthenticatedAs($user);

        // Still exactly one session row - tabs are not separate logins.
        $this->assertSame(1, DB::table('sessions')->where('user_id', $user->id)->count());
    }

    public function test_remember_me_cannot_bypass_single_session_rule(): void
    {
        $user = $this->makeUser('remember-single@test.com');

        // 1) Browser A logs in with "remember me".
        $this->freshRequest();
        $loginA = $this->login($user, remember: true);
        $rememberCookie = $this->cookieValue($loginA, $this->rememberCookieName());

        // 2) Browser B logs into the SAME account (without remember).
        $this->freshRequest();
        $this->login($user);

        // Browser A's session row has been deleted - only B's remains.
        $this->assertSame(1, DB::table('sessions')->where('user_id', $user->id)->count());

        // 3) Browser A returns with ONLY its (now stale) remember cookie. The
        //    restore must NOT create a competing session - it is logged out.
        $this->freshRequest();
        $this->withUnencryptedCookies([$this->rememberCookieName() => $rememberCookie])
            ->get('/dashboard')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_password_change_invalidates_other_sessions(): void
    {
        $user = $this->makeUser('pwchange@test.com');

        $this->freshRequest();
        $response = $this->login($user);
        $sessionCookie = $this->cookieValue($response, $this->sessionCookieName());

        // Simulate a second active session on another device (older than the
        // current one, so the current session remains the "newest").
        $otherSessionId = 'other-device-'.Str::random(10);
        DB::table('sessions')->insert([
            'id' => $otherSessionId,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test agent',
            'payload' => 'dummy',
            'last_activity' => time() - 3600,
        ]);

        $this->assertSame(2, DB::table('sessions')->where('user_id', $user->id)->count());

        // Change the password from the current session.
        $this->freshRequest();
        $this->withUnencryptedCookies([$this->sessionCookieName() => $sessionCookie])
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasNoErrors();

        // The other device's session is dropped; the current one remains.
        $remaining = DB::table('sessions')->where('user_id', $user->id)->pluck('id')->all();
        $this->assertSame([$this->activeSessionId($user)], $remaining);

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }
}
