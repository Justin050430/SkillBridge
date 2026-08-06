<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Mentor\DashboardController as MentorDashboardController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;

Route::get('/', function () {
    return view('welcome');
});

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
});

Route::middleware(['auth', 'role:Mentor'])->group(function () {

    Route::get('/mentor/dashboard', [MentorDashboardController::class, 'index'])
            ->name('mentor.dashboard');
});

Route::middleware(['auth', 'role:Student'])->group(function () {

    Route::get('/student/dashboard', [StudentDashboardController::class, 'index'])
            ->name('student.dashboard');
});

require __DIR__.'/auth.php';
