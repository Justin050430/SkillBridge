<?php

namespace App\Support;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ApiExceptionResponse
{
    public static function render(Throwable $exception, Request $request): JsonResponse
    {
        $status = match (true) {
            $exception instanceof AuthenticationException => 401,
            $exception instanceof ValidationException => $exception->status,
            $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
            default => 500,
        };

        $message = match ($status) {
            401 => 'Authentication required.',
            403 => 'Forbidden.',
            404 => 'Not found.',
            405 => 'Method not allowed.',
            419 => 'Page expired.',
            422 => 'The submitted data is invalid.',
            429 => 'Too many requests. Please try again later.',
            default => $status >= 500
                ? 'An unexpected error occurred. Please try again later.'
                : 'The request could not be processed.',
        };

        $requestId = Ifa::requestId($request);
        $response = $status >= 500
            ? Ifa::error($message, $status, $requestId)
            : Ifa::fail($message, $status, $requestId);

        if ($exception instanceof ValidationException) {
            $response->setData([...$response->getData(true), 'errors' => $exception->errors()]);
        }

        // Retain transport metadata such as Retry-After and Allow.
        if ($exception instanceof HttpExceptionInterface) {
            $response->withHeaders($exception->getHeaders());
        }

        return $response;
    }
}
