<?php

/**
 * @author OOI JIAN XIN
 *
 * Shared helpers for building web services that follow the assignment's
 * Interface Agreement (IFA):
 *
 *   Request  : every request must carry a "timestamp" OR a unique "requestId"
 *              so the request can be tracked.
 *   Response : every response always carries a "status" field
 *              (S = Success | F = Fail | E = Error) and a "timestamp".
 *
 * All web-service endpoints in SkillBridge (Assessment stats, Course
 * catalogue, User profile, Enrollment stats, Notification create) use these
 * helpers so the IFA shape stays identical across modules.
 */

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Ifa
{
    /**
     * The requestId to use for the current request: an explicit value from
     * the client, or a freshly generated UUID when the client did not send one.
     */
    public static function requestId(Request $request): string
    {
        return (string) $request->input('requestId', Str::uuid());
    }

    /**
     * Enforce the IFA request rule. Returns null when the request is
     * traceable (has a timestamp and/or requestId), otherwise a 422 "F"
     * response explaining the requirement.
     */
    public static function assertTraceable(Request $request): ?JsonResponse
    {
        if ($request->filled('timestamp') || $request->filled('requestId')) {
            return null;
        }

        return self::fail(
            'A timestamp or requestId field is required.',
            422,
            self::requestId($request)
        );
    }

    /**
     * IFA "S" (Success) response: status + requestId + timestamp + data.
     */
    public static function success(array $data, ?string $requestId = null): JsonResponse
    {
        return response()->json([
            'status' => 'S',
            'requestId' => $requestId ?? (string) Str::uuid(),
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ]);
    }

    /**
     * IFA "F" (Fail) response: the client's request was understood but could
     * not be fulfilled (validation, missing resource, bad payload).
     */
    public static function fail(string $message, int $status = 422, ?string $requestId = null): JsonResponse
    {
        return response()->json([
            'status' => 'F',
            'requestId' => $requestId ?? (string) Str::uuid(),
            'timestamp' => now()->toIso8601String(),
            'message' => $message,
        ], $status);
    }

    /**
     * IFA "E" (Error) response: an unexpected server-side error occurred.
     * The message is intentionally generic so no internal details leak.
     */
    public static function error(string $message, int $status = 500, ?string $requestId = null): JsonResponse
    {
        return response()->json([
            'status' => 'E',
            'requestId' => $requestId ?? (string) Str::uuid(),
            'timestamp' => now()->toIso8601String(),
            'message' => $message,
        ], $status);
    }
}
