<?php

use App\Http\Controllers\Api\AssessmentApiController;
use App\Http\Controllers\Api\CourseApiController;
use Illuminate\Support\Facades\Route;

Route::get('/courses/published', [CourseApiController::class, 'published'])
    ->middleware('throttle:60,1')
    ->name('api.courses.published');

// Web service exposed by the Assessment & Progress module (IFA-compliant).
// The "web" group is applied so cookie/session authentication works when the
// mentor dashboard widget consumes this service from the browser.
Route::get('/assessments/{assessment}/stats', [AssessmentApiController::class, 'stats'])
    ->middleware(['web', 'auth', 'role:Admin,Mentor', 'throttle:60,1'])
    ->name('api.assessments.stats');
