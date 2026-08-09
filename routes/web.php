<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\QuestionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Mentor\DashboardController as MentorDashboardController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\EnrollmentController as StudentEnrollmentController;
use App\Http\Controllers\Student\AssessmentController as StudentAssessmentController;
use App\Http\Controllers\Student\ProgressController;

Route::get('/', function () {
    return view('landing');
});

// Central post-login destination that dispatches to the role-specific dashboard.
Route::middleware('auth')->get('/dashboard', function () {
    $user = Auth::user();

    $route = match (true) {
        $user->isAdmin() => 'admin.dashboard',
        $user->isMentor() => 'mentor.dashboard',
        default => 'student.dashboard',
    };

    return redirect()->route($route);
})->name('dashboard');

Route::middleware(['auth', 'role:Admin'])->group(function () {

    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
            ->name('admin.dashboard');
    
    Route::get('/admin/users', [UserController::class, 'index'])
            ->name('admin.users');
    
    Route::get('/admin/users/create',
                    [UserController::class, 'create'])
            ->name('admin.users.create');

    Route::post('/admin/users',
                    [UserController::class, 'store'])
            ->name('admin.users.store');
    
    Route::get('/admin/users/{user}/edit',
                    [UserController::class, 'edit'])
            ->name('admin.users.edit');

    Route::put('/admin/users/{user}',
                    [UserController::class, 'update'])
            ->name('admin.users.update');
    
    Route::get('/admin/users/{user}/delete',
                    [UserController::class, 'delete'])
            ->name('admin.users.delete');

    Route::delete('/admin/users/{user}',
                    [UserController::class, 'destroy'])
            ->name('admin.users.destroy');

    Route::get('/admin/courses', [CourseController::class, 'index'])
            ->name('admin.courses');

    Route::get('/admin/courses/create', [CourseController::class, 'create'])
            ->name('admin.courses.create');

    Route::post('/admin/courses', [CourseController::class, 'store'])
            ->name('admin.courses.store');

    Route::get('/admin/courses/{course:slug}/edit', [CourseController::class, 'edit'])
            ->name('admin.courses.edit');

    Route::put('/admin/courses/{course:slug}', [CourseController::class, 'update'])
            ->name('admin.courses.update');

    Route::get('/admin/courses/{course:slug}/delete', [CourseController::class, 'delete'])
            ->name('admin.courses.delete');

    Route::delete('/admin/courses/{course:slug}', [CourseController::class, 'destroy'])
            ->name('admin.courses.destroy');
});

// Assessment & Question Management - accessible to both Admin and Mentor.
// Mentors are scoped to their own courses inside the controllers.
Route::middleware(['auth', 'role:Admin,Mentor'])->group(function () {

    Route::get('/assessments', [AssessmentController::class, 'index'])
            ->name('assessments.index');

    Route::get('/assessments/create', [AssessmentController::class, 'create'])
            ->name('assessments.create');

    Route::post('/assessments', [AssessmentController::class, 'store'])
            ->name('assessments.store');

    Route::get('/assessments/{assessment:slug}/edit', [AssessmentController::class, 'edit'])
            ->name('assessments.edit');

    Route::put('/assessments/{assessment:slug}', [AssessmentController::class, 'update'])
            ->name('assessments.update');

    Route::get('/assessments/{assessment:slug}/delete', [AssessmentController::class, 'delete'])
            ->name('assessments.delete');

    Route::delete('/assessments/{assessment:slug}', [AssessmentController::class, 'destroy'])
            ->name('assessments.destroy');

    // Questions nested under an assessment

    Route::get('/assessments/{assessment:slug}/questions', [QuestionController::class, 'index'])
            ->name('assessments.questions.index');

    Route::get('/assessments/{assessment:slug}/questions/create', [QuestionController::class, 'create'])
            ->name('assessments.questions.create');

    Route::post('/assessments/{assessment:slug}/questions', [QuestionController::class, 'store'])
            ->name('assessments.questions.store');

    Route::get('/assessments/{assessment:slug}/questions/{question}/edit', [QuestionController::class, 'edit'])
            ->name('assessments.questions.edit');

    Route::put('/assessments/{assessment:slug}/questions/{question}', [QuestionController::class, 'update'])
            ->name('assessments.questions.update');

    Route::delete('/assessments/{assessment:slug}/questions/{question}', [QuestionController::class, 'destroy'])
            ->name('assessments.questions.destroy');

    // Lessons nested under a course (uses the course slug for clean URLs).

    Route::get('/courses/{course:slug}/lessons', [LessonController::class, 'index'])
            ->name('courses.lessons.index');

    Route::get('/courses/{course:slug}/lessons/create', [LessonController::class, 'create'])
            ->name('courses.lessons.create');

    Route::post('/courses/{course:slug}/lessons', [LessonController::class, 'store'])
            ->name('courses.lessons.store');

    Route::get('/courses/{course:slug}/lessons/{lesson}/edit', [LessonController::class, 'edit'])
            ->name('courses.lessons.edit');

    Route::put('/courses/{course:slug}/lessons/{lesson}', [LessonController::class, 'update'])
            ->name('courses.lessons.update');

    Route::get('/courses/{course:slug}/lessons/{lesson}/delete', [LessonController::class, 'delete'])
            ->name('courses.lessons.delete');

    Route::delete('/courses/{course:slug}/lessons/{lesson}', [LessonController::class, 'destroy'])
            ->name('courses.lessons.destroy');
});

// Enrollment management - Admin (full oversight) + Mentor (own courses only).
Route::middleware(['auth', 'role:Admin,Mentor'])->group(function () {

    Route::get('/enrollments', [EnrollmentController::class, 'index'])
            ->name('enrollments.index');

    Route::post('/enrollments/{enrollment}/complete', [EnrollmentController::class, 'complete'])
            ->name('enrollments.complete');

    Route::post('/enrollments/{enrollment}/drop', [EnrollmentController::class, 'drop'])
            ->name('enrollments.drop');
});

Route::middleware(['auth', 'role:Mentor'])->group(function () {

    Route::get('/mentor/dashboard', [MentorDashboardController::class, 'index'])
            ->name('mentor.dashboard');
});

Route::middleware(['auth', 'role:Student'])->group(function () {

    Route::get('/student/dashboard', [StudentDashboardController::class, 'index'])
            ->name('student.dashboard');

    Route::get('/student/courses', [StudentCourseController::class, 'index'])
            ->name('student.courses');

    Route::post('/student/courses/{course:slug}/enroll', [StudentEnrollmentController::class, 'store'])
            ->name('student.enroll');

    // Assessments

    Route::get('/student/assessments', [StudentAssessmentController::class, 'index'])
            ->name('student.assessments.index');

    Route::get('/student/assessments/{assessment:slug}/take', [StudentAssessmentController::class, 'take'])
            ->name('student.assessments.take');

    Route::post('/student/assessments/{assessment:slug}/submit', [StudentAssessmentController::class, 'submit'])
            ->name('student.assessments.submit');

    Route::get('/student/assessments/{assessment:slug}/result', [StudentAssessmentController::class, 'result'])
            ->name('student.assessments.result');

    // Progress

    Route::get('/student/progress', [ProgressController::class, 'index'])
            ->name('student.progress');

    Route::post('/student/progress/{lesson}', [ProgressController::class, 'update'])
            ->name('student.progress.update');
});

// User profile management.
Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
            ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
            ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
            ->name('profile.destroy');
});

// Notifications inbox - available to every authenticated role.
Route::middleware('auth')->group(function () {

    Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
            ->name('notifications.read');

    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
            ->name('notifications.read-all');
});

require __DIR__.'/auth.php';
