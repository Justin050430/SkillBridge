<?php

/**
 * @author OOI JIAN XIN
 *
 * Business rule: each account may be logged in from only ONE browser/session
 * at a time. A new login invalidates the previous session.
 *
 * This middleware enforces the rule server-side on every web request. The
 * database-backed sessions table is the source of truth:
 *
 *   - When a user logs in, AuthenticatedSessionController deletes all of the
 *     user's OTHER session rows, so the newest login wins.
 *   - On each request we compare the current session id against the newest
 *     session row for the user. If the current session is stale (a newer
 *     session exists - including a session restored from a "remember me"
 *     cookie after its row was deleted), we log the session out.
 *
 * Same-browser multiple tabs share one session cookie, so they are never
 * treated as separate logins.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleActiveSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force user resolution - this also triggers a "remember me" restore
        // when a valid remember cookie is present (the restore writes the
        // auth key into the current session).
        $user = $request->user();

        $session = $request->session();

        // Only enforce the rule when the user is authenticated within THIS
        // request's session. The guard may cache a user across requests in a
        // long-running process (and in feature tests), so we must not trust
        // the cached user alone - otherwise a fresh login request would be
        // mistaken for an already-active session.
        if ($user === null || ! $session->has('login_web_'.sha1(SessionGuard::class))) {
            return $next($request);
        }

        $currentSessionId = $session->getId();

        // The newest session row for this user. Under the array session
        // driver (tests) no rows exist, so this stays null and the check is a
        // no-op. Under the database driver the current session's own row is
        // written at the end of the previous request, so a live session
        // matches; a session that was invalidated by a newer login, or a
        // session freshly restored from a remember-me cookie (whose row has
        // not been written yet), does not.
        $newestSessionId = DB::table('sessions')
            ->where('user_id', $user->getAuthIdentifier())
            ->orderByDesc('last_activity')
            ->value('id');

        if ($newestSessionId !== null && $newestSessionId !== $currentSessionId) {
            // This session is no longer the account's active session. Log it
            // out, discard it and return to the login page. Logging out also
            // regenerates the user's remember token, so the stale remember-me
            // cookie cannot silently restore the session.
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->is('api/*')) {
                throw new AuthenticationException;
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}
