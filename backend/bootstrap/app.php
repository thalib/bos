<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register global middleware that runs on every request
        $middleware->append(\App\Http\Middleware\JsonResponseMiddleware::class);
        $middleware->append(\App\Http\Middleware\Cors::class);

        // Register Sanctum middleware
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);

        // You can also specify middleware groups or route-specific middleware
        // $middleware->web([...]);
        // $middleware->api([...]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle API exceptions with standardized responses
        $exceptions->render(function (Throwable $e, $request) {
            // Only apply custom handling to API routes
            if ($request->expectsJson() || $request->is('api/*')) {
                // Handle JSON parsing errors
                if ($e instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
                    $response = $e->getResponse();
                    if ($response->getStatusCode() === 400) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid JSON payload',
                            'error' => [
                                'code' => 'INVALID_JSON',
                                'message' => 'Invalid JSON payload',
                                'details' => []
                            ]
                        ], 400);
                    }
                }

                // Handle malformed JSON requests
                if ($e instanceof \JsonException || 
                    ($e instanceof \UnexpectedValueException && str_contains($e->getMessage(), 'JSON'))) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid JSON payload',
                        'error' => [
                            'code' => 'INVALID_JSON',
                            'message' => 'Invalid JSON payload',
                            'details' => []
                        ]
                    ], 400);
                }

                // Handle authentication errors
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Authentication required',
                        'error' => [
                            'code' => 'UNAUTHORIZED',
                            'message' => 'Authentication required',
                            'details' => []
                        ]
                    ], 401);
                }

                // Handle authorization errors
                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access denied',
                        'error' => [
                            'code' => 'ACCESS_DENIED',
                            'message' => 'Access denied',
                            'details' => []
                        ]
                    ], 403);
                }

                // Handle model not found errors
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Resource not found',
                        'error' => [
                            'code' => 'RESOURCE_NOT_FOUND',
                            'message' => 'Resource not found',
                            'details' => []
                        ]
                    ], 404);
                }

                // Handle method not allowed errors
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Method not allowed',
                        'error' => [
                            'code' => 'METHOD_NOT_ALLOWED',
                            'message' => 'Method not allowed',
                            'details' => []
                        ]
                    ], 405);
                }

                // Handle route not found errors
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Endpoint not found',
                        'error' => [
                            'code' => 'NOT_FOUND',
                            'message' => 'Endpoint not found',
                            'details' => []
                        ]
                    ], 404);
                }

                // Handle validation errors (these are typically handled by the controller, but just in case)
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'error' => [
                            'code' => 'VALIDATION_FAILED',
                            'message' => 'Validation failed',
                            'details' => $e->errors()
                        ]
                    ], 422);
                }

                // Handle all other exceptions with a generic error response
                Log::error('API Exception: ' . $e->getMessage(), [
                    'exception' => $e,
                    'request' => $request->all()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Internal server error',
                    'error' => [
                        'code' => 'INTERNAL_SERVER_ERROR',
                        'message' => 'Internal server error',
                        'details' => []
                    ]
                ], 500);
            }

            // For non-API requests, let Laravel handle normally
            return null;
        });
    })->create();
