<?php

use App\Http\Controllers\TestTemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Development Test Routes
|--------------------------------------------------------------------------
|
| These routes are only available during development and testing.
| They will not be included in production builds.
|
*/

// Public test routes (no authentication required)
Route::prefix('test')->group(function () {

    // Health check for development
    Route::get('/ping', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'pong',
                'timestamp' => now()->toISOString(),
                'environment' => app()->environment(),
            ],
            'message' => 'Test endpoint is working',
        ]);
    });

    // Route for testing templates with demo data
    Route::get('/test-template/{template}', [TestTemplateController::class, 'show']);

    // Simple test route
    Route::get('/test', function () {
        return response()->json(['message' => 'API test route is working']);
    });

});
