<?php

/**
 * @author ZHI FOOK CHUA
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Support\Ifa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CourseApiController extends Controller
{
    public function published(Request $request): JsonResponse
    {
        $requestId = Ifa::requestId($request);

        // IFA: requests must be traceable via timestamp and/or requestId.
        if ($missing = Ifa::assertTraceable($request)) {
            return $missing;
        }

        try {
            $courses = Course::with(['mentor', 'category'])
                ->where('status', 'Published')
                ->orderBy('title')
                ->get()
                ->map(function (Course $course) {
                    return [
                        'id' => $course->id,
                        'title' => $course->title,
                        'slug' => $course->slug,
                        'description' => $course->description,
                        'thumbnail' => $course->thumbnail,
                        'category' => $course->category->category_name,
                        'mentor' => $course->mentor->first_name.' '.$course->mentor->last_name,
                    ];
                })
                ->values();

            return Ifa::success($courses->all(), $requestId);
        } catch (Throwable $exception) {
            report($exception);

            return Ifa::error('The course catalogue is temporarily unavailable.', 500, $requestId);
        }
    }
}
