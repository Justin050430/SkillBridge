<?php

/**
 * @author ZHI FOOK CHUA
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Throwable;

class CourseApiController extends Controller
{
    public function published(): JsonResponse
    {
        $requestId = (string) Str::uuid();

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

            return response()->json([
                'status' => 'S',
                'requestId' => $requestId,
                'timestamp' => now()->toIso8601String(),
                'data' => $courses,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'E',
                'requestId' => $requestId,
                'timestamp' => now()->toIso8601String(),
                'message' => 'The course catalogue is temporarily unavailable.',
            ], 500);
        }
    }
}
