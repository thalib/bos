<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class JsonResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force Accept header to application/json for API routes
        if (str_starts_with($request->path(), 'api/')) {
            $request->headers->set('Accept', 'application/json');
        }

        // Process the request
        $response = $next($request);

        // If it's an API route, ensure it returns JSON
        if (str_starts_with($request->path(), 'api/')) {
            // Check if the response is not already a JSON response
            if (!($response instanceof JsonResponse) && !str_contains($response->headers->get('Content-Type') ?? '', 'application/json')) {
                $statusCode = $response->getStatusCode();
                $message = Response::$statusTexts[$statusCode] ?? 'Unknown error';

                // For 404 errors on API routes
                if ($statusCode === 404) {
                    return response()->json([
                        'error' => 'Not Found',
                        'message' => 'Resource not found',
                    ], 404);
                }

                // For 500 errors on API routes
                if ($statusCode === 500) {
                    return response()->json([
                        'error' => 'Server Error',
                        'message' => config('app.debug') ? $response->getContent() : 'Internal server error',
                    ], 500);
                }

                // For other errors
                return response()->json([
                    'error' => $message,
                    'message' => $message,
                ], $statusCode);
            }
        }

        return $response;
    }
}
