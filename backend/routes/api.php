<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Simple test route
Route::get('/test', function () {
    return response()->json(['message' => 'API test route is working']);
});

// V1 API Routes
Route::prefix('v1')->group(function () {    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
        Route::post('register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
        Route::post('refresh', [\App\Http\Controllers\Api\AuthController::class, 'refresh']);
        Route::post('logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('status', [\App\Http\Controllers\Api\AuthController::class, 'status']);
    });

    // Menu routes
    Route::middleware('auth:sanctum')->get('menu', [\App\Http\Controllers\Api\MenuController::class, 'index']);

    // Document routes
    Route::prefix('documents')->middleware('auth:sanctum')->group(function () {
        Route::post('generate-pdf', [\App\Http\Controllers\DocumentController::class, 'generatePdf']);
        Route::get('templates', [\App\Http\Controllers\DocumentController::class, 'getTemplates']);
        Route::post('preview', [\App\Http\Controllers\DocumentController::class, 'previewDocument']);
        Route::get('templates/{template}', [\App\Http\Controllers\DocumentController::class, 'getTemplateInfo']);
        Route::post('validate', [\App\Http\Controllers\DocumentController::class, 'validateTemplateData']);
    });

});
