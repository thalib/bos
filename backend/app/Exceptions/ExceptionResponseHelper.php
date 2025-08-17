<?php

namespace App\Exceptions;

use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Small helper to centralize exception -> JSON response mappings used by
 * both the global Handler and bootstrapped exception renderer.
 */
class ExceptionResponseHelper
{
    /**
     * If the throwable is a ThrottleRequestsException for an API route,
     * return the standardized JSON envelope response. Otherwise return null.
     */
    public static function throttleJsonResponse(Throwable $e, $request): ?JsonResponse
    {
        if (! ($e instanceof ThrottleRequestsException)) {
            return null;
        }

        // Only apply to API routes / JSON expectations
        if (! ($request->expectsJson() || $request->is('api/*'))) {
            return null;
        }

        $headers = method_exists($e, 'getHeaders') ? $e->getHeaders() : [];
        $message = 'Too many login attempts. Please try again later.';

        $payload = [
            'success' => false,
            'message' => $message,
            'error' => [
                'code' => 'auth.rate_limited',
                'message' => $message,
                'details' => [],
            ],
        ];

        return new JsonResponse($payload, 429, $headers);
    }
}
