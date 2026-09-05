<?php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class ServiceExchangeLog
{
    public static function record(string $endpoint, string $requestId, ?Response $response, bool $fallback): void
    {
        try {
            $payload = $response?->json();
            Log::channel('web_services')->info('Service exchange', [
                'timestamp' => now()->toIso8601String(),
                'endpoint' => $endpoint,
                'requestId' => $requestId,
                'http_status' => $response?->status(),
                'response_status' => $payload['status'] ?? null,
                'response_requestId' => $payload['requestId'] ?? null,
                'response_timestamp' => $payload['timestamp'] ?? null,
                'data_source' => $fallback ? 'database_fallback' : 'http',
                // No cookies, passwords, names, emails or notification bodies.
                'response_data' => Arr::only((array) ($payload['data'] ?? []), [
                    'user_id', 'notification_id', 'total', 'active', 'completed', 'dropped',
                ]),
            ]);
        } catch (Throwable) {
            // Diagnostic logging must not turn HTTP success into a fallback.
            error_log('Web service exchange log could not be written.');
        }
    }
}
