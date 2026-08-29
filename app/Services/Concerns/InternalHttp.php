<?php

/**
 * @author OOI JIAN XIN
 *
 * Shared concern for modules that CONSUME a SkillBridge web service
 * server-side (module-to-module integration).
 *
 * Laravel's Http client does not automatically carry the browser's session,
 * so an internal call to an auth-protected endpoint would normally receive a
 * 401. This trait forwards the current request's session cookie (and CSRF
 * token) so the internal endpoint recognises the same authenticated caller.
 * If the current request has no cookie (e.g. in a console job or a test),
 * the call simply proceeds without credentials and the caller handles the
 * failure gracefully.
 */

namespace App\Services\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

trait InternalHttp
{
    /**
     * Build an Http client that re-uses the current session so protected
     * internal API endpoints authenticate the same user.
     *
     * When there is no active HTTP request/session (e.g. a console job, or a
     * unit-style test calling the client directly), the call simply proceeds
     * without credentials and the caller handles the failure gracefully.
     */
    protected function internalHttp(): PendingRequest
    {
        $http = Http::timeout(5)->acceptJson();

        $request = request();

        if ($request === null) {
            return $http;
        }

        try {
            $sessionName = session()->getName();
            $sessionValue = (string) $request->cookie($sessionName, '');

            $http = $http->withHeaders([
                'Cookie' => $sessionName.'='.$sessionValue,
                'X-CSRF-TOKEN' => session()->token(),
            ]);
        } catch (\Throwable $exception) {
            // No usable session context - call without credentials.
        }

        return $http;
    }
}
