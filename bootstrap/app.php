<?php

use App\Services\SecurityService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
                        'role' => \App\Http\Middleware\RoleMiddleware::class,
                    ]);
                })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Secure coding: log every access-control failure (audit trail).
        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() === 403) {
                SecurityService::logAccessControlFailure(
                    $request,
                    $exception->getMessage() ?: 'Forbidden'
                );
            }
        });

        // Secure coding: never disclose internal details in error responses.
        $exceptions->render(function (Throwable $exception, Request $request) {
            // Authentication failures are handled by the framework (401/redirect).
            if ($exception instanceof AuthenticationException) {
                return;
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                $status = $exception instanceof HttpException
                    ? $exception->getStatusCode()
                    : 500;

                // Sanitise every JSON/API error (4xx and 5xx) so internal
                // details can never leak, even in a debug environment.
                // Validation responses (422) keep their field messages.
                if ($status >= 400 && $status !== 422) {
                    return response()->json(
                        SecurityService::safeErrorPayload($exception, $status),
                        $status
                    );
                }
            }
        });
    })->create();
