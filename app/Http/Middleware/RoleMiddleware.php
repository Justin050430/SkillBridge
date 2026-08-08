<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if ($user === null || !in_array($user->role->role_name, $roles, true)) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}