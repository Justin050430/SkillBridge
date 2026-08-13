<?php

/**
 * @author YIK HANG YIP
 *
 * Session / communication security for the Notification module.
 * Enforces HTTPS on every request in the production environment, so session
 * identifiers and notification data are never transmitted in plain text.
 * In non-production environments this middleware is a no-op, so local
 * development over http:// keeps working unchanged.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSecureConnection
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production') && ! $request->secure()) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
