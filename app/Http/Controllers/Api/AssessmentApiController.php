<?php

/**
 * @author OOI JIAN XIN
 *
 * Web service EXPOSED by the Assessment & Progress module.
 *
 * IFA (Interface Agreement):
 *   Request  : must carry a "timestamp" (or "requestId") field for tracking.
 *   Response : always carries "status" (S/F/E) and "timestamp".
 *
 * Consumed by: the Mentor dashboard analytics widget (and any other module
 * that needs aggregate assessment statistics).
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\User;
use App\Support\Ifa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class AssessmentApiController extends Controller
{
    /**
     * Expose aggregate statistics for a single assessment.
     */
    public function stats(Request $request, Assessment $assessment): JsonResponse
    {
        $requestId = (string) $request->input('requestId', Str::uuid());

        // IFA: every request must be traceable via timestamp and/or requestId.
        if (!$request->filled('timestamp') && !$request->filled('requestId')) {
            return response()->json([
                'status' => 'F',
                'requestId' => $requestId,
                'timestamp' => now()->toIso8601String(),
                'message' => 'A timestamp or requestId field is required.',
            ], 422);
        }

        // Access control: mentors may only view statistics for their own
        // assessments; admins may view all.
        $user = Auth::user();

        if ($user instanceof User && $user->isMentor()) {
            $owns = (int) $assessment->lesson->course->mentor_id === (int) $user->id;
            abort_unless($owns, 403, 'You can only view statistics for your own assessments.');
        }

        try {
            $attempts = AssessmentAttempt::where('assessment_id', $assessment->id)
                ->whereNotNull('submitted_at')
                ->get();

            $total = $attempts->count();

            $data = [
                'assessment_id' => $assessment->id,
                'assessment_title' => $assessment->title,
                'passing_score' => $assessment->passing_score,
                'question_count' => $assessment->questions()->count(),
                'attempt_count' => $total,
                'average_score' => $total > 0 ? round((float) $attempts->avg('percentage'), 2) : null,
                'pass_rate' => $total > 0 ? round($attempts->where('is_passed', true)->count() / $total * 100, 2) : null,
            ];

            return response()->json([
                'status' => 'S',
                'requestId' => $requestId,
                'timestamp' => now()->toIso8601String(),
                'data' => $data,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'E',
                'requestId' => $requestId,
                'timestamp' => now()->toIso8601String(),
                'message' => 'The assessment statistics are temporarily unavailable.',
            ], 500);
        }
    }

    /**
     * Expose aggregate statistics for every assessment in a course.
     *
     * Consumed by the Course Management module (course edit page) so a course
     * owner can see how its assessments are performing. Mentors are scoped to
     * their own courses; admins may view any course.
     */
    public function courseStats(Request $request, Course $course): JsonResponse
    {
        $requestId = Ifa::requestId($request);

        // IFA: requests must be traceable via timestamp and/or requestId.
        if ($missing = Ifa::assertTraceable($request)) {
            return $missing;
        }

        // Access control: mentors may only view statistics for their own
        // courses; admins may view all.
        $user = Auth::user();

        if ($user instanceof User && $user->isMentor()) {
            $owns = (int) $course->mentor_id === (int) $user->id;
            abort_unless($owns, 403, 'You can only view statistics for your own courses.');
        }

        try {
            $assessmentIds = $course->lessons()
                ->with('assessments')
                ->get()
                ->flatMap->assessments
                ->pluck('id');

            $attempts = AssessmentAttempt::whereIn('assessment_id', $assessmentIds)
                ->whereNotNull('submitted_at')
                ->get();

            $total = $attempts->count();

            $questionCount = Assessment::whereIn('id', $assessmentIds)
                ->withCount('questions')
                ->get()
                ->sum('questions_count');

            $data = [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'assessment_count' => $assessmentIds->count(),
                'question_count' => $questionCount,
                'attempt_count' => $total,
                'average_score' => $total > 0 ? round((float) $attempts->avg('percentage'), 2) : null,
                'pass_rate' => $total > 0 ? round($attempts->where('is_passed', true)->count() / $total * 100, 2) : null,
            ];

            return Ifa::success($data, $requestId);
        } catch (Throwable $exception) {
            report($exception);

            return Ifa::error('The course assessment statistics are temporarily unavailable.', 500, $requestId);
        }
    }
}
