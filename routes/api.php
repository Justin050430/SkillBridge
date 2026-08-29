<?php

use App\Http\Controllers\Api\AssessmentApiController;
use App\Http\Controllers\Api\CourseApiController;
use App\Http\Controllers\Api\EnrollmentApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\UserApiController;
use Illuminate\Support\Facades\Route;

// Web service exposed by the Course Management module (ZHI FOOK CHUA).
// IFA-compliant: requires timestamp/requestId in the request and returns
// status + timestamp in the response. Consumed by the Assessment module
// (CourseApiClient) and the student course catalogue.
Route::get('/courses/published', [CourseApiController::class, 'published'])
    ->middleware('throttle:60,1')
    ->name('api.courses.published');

// Web service exposed by the User Management module (ZHAN ZHENG PUU).
// Consumed by the Notification module (UserProfileClient).
Route::get('/users/{user}/profile', [UserApiController::class, 'profile'])
    ->middleware(['web', 'auth', 'throttle:60,1'])
    ->name('api.users.profile');

// Web service exposed by the Enrollment Management module (GEN TECK NG).
// Consumed by the User Management module (EnrollmentStatsClient).
Route::get('/enrollments/user/{user}/stats', [EnrollmentApiController::class, 'userStats'])
    ->middleware(['web', 'auth', 'throttle:60,1'])
    ->name('api.enrollments.user.stats');

// Web service exposed by the Assessment & Progress module (OOI JIAN XIN) -
// course-level analytics. Consumed by the Course Management module
// (CourseAnalyticsClient) on the course edit page.
// NOTE: registered before the per-assessment route; no segment overlap.
Route::get('/assessments/course/{course}/stats', [AssessmentApiController::class, 'courseStats'])
    ->middleware(['web', 'auth', 'role:Admin,Mentor', 'throttle:60,1'])
    ->name('api.assessments.course.stats');

// Web service exposed by the Assessment & Progress module (OOI JIAN XIN) -
// single assessment statistics. Consumed by the mentor dashboard widget.
// The "web" group is applied so cookie/session authentication works when the
// mentor dashboard widget consumes this service from the browser.
Route::get('/assessments/{assessment}/stats', [AssessmentApiController::class, 'stats'])
    ->middleware(['web', 'auth', 'role:Admin,Mentor', 'throttle:60,1'])
    ->name('api.assessments.stats');

// Web service exposed by the Notification module (YIK HANG YIP).
// Consumed by the Enrollment Management module (NotificationApiClient) when
// an enrollment is completed or dropped.
Route::post('/notifications', [NotificationApiController::class, 'store'])
    ->middleware(['web', 'auth', 'role:Admin,Mentor', 'throttle:60,1'])
    ->name('api.notifications.store');
