<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Route;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        // Scoped route model binding: ensure notifications are scoped to the authenticated user
        Route::model('notification', Notification::class);
        Route::bind('notification', function ($value) {
            $notification = Notification::find($value);

            // Scope to authenticated user: only allow access to own notifications
            if ($notification && $notification->user_id === Auth::id()) {
                return $notification;
            }

            return null;  // Will result in 404 if not found or not owned
        });
    }
}
