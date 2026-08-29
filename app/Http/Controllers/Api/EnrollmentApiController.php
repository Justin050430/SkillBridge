<?php

/**
 * @author GEN TECK NG
 *
 * Web service EXPOSED by the Enrollment Management module.
 *
 * IFA (Interface Agreement):
 *   Request  : must carry a "timestamp" (or "requestId") field for tracking.
 *   Response : always carries "status" (S/F/E), "requestId" and "timestamp".
 *
 * GET /api/enrollments/user/{user}/stats
 *   Returns a user's enrolment statistics (total / active / completed /
 *   dropped). Admins may query any user; a non-admin may only query their own
 *   statistics. Consumed by the User Management module (admin user edit page).
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\Ifa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class EnrollmentApiController extends Controller
{
    public function userStats(Request $request, User $user): JsonResponse
    {
        $requestId = Ifa::requestId($request);

        // IFA: requests must be traceable via timestamp and/or requestId.
        if ($missing = Ifa::assertTraceable($request)) {
            return $missing;
        }

        // Access control: admins may view anyone; other users may only view
        // their own statistics.
        $viewer = Auth::user();

        if (! $viewer instanceof User) {
            return Ifa::fail('Authentication required.', 401, $requestId);
        }

        if (! $viewer->isAdmin() && (int) $viewer->id !== (int) $user->id) {
            return Ifa::fail('You can only view your own enrollment statistics.', 403, $requestId);
        }

        try {
            $enrollments = Enrollment::where('student_id', $user->id)->get();

            $data = [
                'user_id' => $user->id,
                'total' => $enrollments->count(),
                'active' => $enrollments->where('status', 'Active')->count(),
                'completed' => $enrollments->where('status', 'Completed')->count(),
                'dropped' => $enrollments->where('status', 'Dropped')->count(),
            ];

            return Ifa::success($data, $requestId);
        } catch (Throwable $exception) {
            report($exception);

            return Ifa::error('The enrollment statistics are temporarily unavailable.', 500, $requestId);
        }
    }
}
