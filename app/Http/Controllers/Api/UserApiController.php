<?php

/**
 * @author ZHAN ZHENG PUU
 *
 * Web service EXPOSED by the User Management module.
 *
 * IFA (Interface Agreement):
 *   Request  : must carry a "timestamp" (or "requestId") field for tracking.
 *   Response : always carries "status" (S/F/E), "requestId" and "timestamp".
 *
 * GET /api/users/{user}/profile
 *   Returns a user's public profile (id, name, role, status). Consumed by the
 *   Notification module (recipient validation/name resolution) and by any
 *   other module that needs basic user information.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Ifa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class UserApiController extends Controller
{
    public function profile(Request $request, User $user): JsonResponse
    {
        $requestId = Ifa::requestId($request);

        // IFA: requests must be traceable via timestamp and/or requestId.
        if ($missing = Ifa::assertTraceable($request)) {
            return $missing;
        }

        try {
            $data = [
                'user_id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role?->role_name,
                'status' => $user->status,
            ];

            return Ifa::success($data, $requestId);
        } catch (Throwable $exception) {
            report($exception);

            return Ifa::error('The user profile is temporarily unavailable.', 500, $requestId);
        }
    }
}
