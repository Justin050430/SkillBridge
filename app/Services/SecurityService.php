<?php

/**
 * @author OOI JIAN XIN
 *
 * Centralised secure-coding helpers for the Assessment & Progress module:
 *
 *  1. Logging access-control failures (audit trail / non-repudiation).
 *  2. Producing safe error payloads that never disclose sensitive internal
 *     details (information-disclosure prevention).
 *
 * Records written to the "security" channel deliberately omit sensitive
 * values such as session identifiers, hashes and account credentials.
 */

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class SecurityService
{
    /**
     * Write an audit entry every time an access-control failure occurs
     * (e.g. a 403 response from the role middleware or a module guard).
     *
     * Deliberately wrapped in a guard so a logging failure (e.g. a transient
     * disk/permission issue in the production environment) can never turn the
     * 403 response into a 500 - the client still receives the Forbidden
     * response either way.
     */
    public static function logAccessControlFailure(Request $request, string $reason = 'Forbidden'): void
    {
        try {
            $user = Auth::user();

            Log::channel('security')->warning('Access control failure', [
                'timestamp' => now()->toIso8601String(),
                'user_id' => $user?->getAuthIdentifier(),
                'user_role' => $user instanceof User ? $user->role?->role_name : 'guest',
                'ip_address' => $request->ip(),
                'method' => $request->method(),
                'path' => $request->path(),
                'reason' => $reason,
            ]);
        } catch (Throwable $exception) {
            // Never break the request because the audit log could not be
            // written. Surface the failure to the PHP error log (independent
            // of the Laravel log channels) without recursing through them.
            error_log('Security audit logging failed: '.$exception->getMessage());
        }
    }

    /**
     * Build a generic error payload for API responses. Internal exception
     * details (stack traces, SQL, class names, session/account information)
     * are intentionally excluded so that they cannot leak to the client.
     */
    public static function safeErrorPayload(Throwable $exception, int $status = 500): array
    {
        $messages = [
            403 => 'Forbidden.',
            404 => 'Not found.',
            419 => 'Page expired.',
            500 => 'An unexpected error occurred. Please try again later.',
        ];

        return [
            'status' => 'E',
            'timestamp' => now()->toIso8601String(),
            'message' => $messages[$status] ?? 'An unexpected error occurred. Please try again later.',
        ];
    }
}
