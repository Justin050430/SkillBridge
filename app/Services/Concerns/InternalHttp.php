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
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use LogicException;

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
        $http = Http::timeout(5)->acceptJson()->withoutRedirecting();

        // Credentials must only travel to the configured application origin.
        $origin = static fn (string $url): array => [
            strtolower((string) parse_url($url, PHP_URL_SCHEME)),
            strtolower((string) parse_url($url, PHP_URL_HOST)),
            parse_url($url, PHP_URL_PORT) ?: (strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? 443 : 80),
        ];
        $expectedOrigin = $origin((string) config('app.url'));
        $http->beforeSending(function (ClientRequest $outgoing) use ($origin, $expectedOrigin): void {
            if ($origin($outgoing->url()) !== $expectedOrigin) {
                throw new LogicException('Internal web services must use the configured application origin.');
            }
        });

        $request = request();

        if ($request === null) {
            return $http;
        }

        try {
            $sessionName = session()->getName();
            // EncryptCookies has already decrypted request()->cookie(). Keep
            // the original wire value from the header for the next HTTP hop.
            $cookies = (string) $request->headers->get('Cookie', '');
            if (preg_match('/(?:^|;\s*)'.preg_quote($sessionName, '/').'=([^;]*)/', $cookies, $matches)) {
                $http = $http->withHeaders([
                    'Cookie' => $sessionName.'='.$matches[1],
                    'X-CSRF-TOKEN' => session()->token(),
                ]);
            }
        } catch (\Throwable $exception) {
            // No usable session context - call without credentials.
        }

        return $http;
    }
}
