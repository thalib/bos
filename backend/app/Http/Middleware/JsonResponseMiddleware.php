<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonResponseMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force Accept header to application/json for API routes
        if (str_starts_with($request->path(), 'api/')) {
            $request->headers->set('Accept', 'application/json');
        }

        // Process the request
        $response = $next($request);

        // If it's an API route, ensure it returns JSON in the standardized project envelope
        if (str_starts_with($request->path(), 'api/')) {
            // If response is already a JsonResponse, return as-is
            if ($response instanceof JsonResponse || str_contains($response->headers->get('Content-Type') ?? '', 'application/json')) {
                return $response;
            }

            $statusCode = $response->getStatusCode();

            // Map common status codes to project error codes/messages
            switch ($statusCode) {
                case 400:
                    $code = 'BAD_REQUEST';
                    $message = 'Bad request';
                    break;
                case 401:
                    $code = 'UNAUTHORIZED';
                    $message = 'Authentication required';
                    break;
                case 403:
                    $code = 'ACCESS_DENIED';
                    $message = 'Access denied';
                    break;
                case 404:
                    $code = 'NOT_FOUND';
                    $message = 'Resource not found';
                    break;
                case 405:
                    $code = 'METHOD_NOT_ALLOWED';
                    $message = 'Method not allowed';
                    break;
                case 422:
                    $code = 'VALIDATION_FAILED';
                    $message = 'Validation failed';
                    break;
                case 500:
                default:
                    $code = 'INTERNAL_SERVER_ERROR';
                    $message = 'Internal server error';
                    break;
            }

            $payload = [
                'success' => false,
                'message' => $message,
                'error' => [
                    'code' => $code,
                    'message' => $message,
                    'details' => [],
                ],
            ];

            // Preserve existing response headers (like X-Request-Id) when possible
            $headers = $response->headers->all();

            return response()->json($payload, $statusCode, $headers);
        }

        return $response;
    }
}
