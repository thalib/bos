<?php

namespace App\Providers;

use App\Attributes\ApiResource;
use App\Http\Controllers\ApiResourceController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;

class ApiResourceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Can be empty or used for binding
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->discoverApiResources();
    }

    /**
     * Discover and register API resources from models with the ApiResource attribute.
     */
    protected function discoverApiResources(): void
    {
        // Get the path to the app/Models directory
        $modelsPath = app_path('Models');

        // Check if the Models directory exists
        if (!File::isDirectory($modelsPath)) {
            Log::warning('Models directory not found at: ' . $modelsPath);
            return;
        }

        // Get all PHP files in the Models directory
        $modelFiles = File::files($modelsPath);

        foreach ($modelFiles as $file) {
            try {
                // Get the file name without extension
                $fileName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                
                // Construct the full class name
                $className = "App\\Models\\{$fileName}";

                // Check if the class exists
                if (!class_exists($className)) {
                    continue;
                }

                // Create reflection class
                $reflectionClass = new ReflectionClass($className);

                // Skip abstract classes and interfaces
                if ($reflectionClass->isAbstract() || $reflectionClass->isInterface()) {
                    continue;
                }

                // Check if the class has the ApiResource attribute
                $attributes = $reflectionClass->getAttributes(ApiResource::class);

                if (empty($attributes)) {
                    continue;
                }

                // Get the first ApiResource attribute instance
                $apiResourceAttribute = $attributes[0]->newInstance();

                // Extract configuration from the attribute
                $apiPrefix = $apiResourceAttribute->apiPrefix;
                $version = $apiResourceAttribute->version;
                $uri = $apiResourceAttribute->uri;

                // If URI is null, derive it from the model name
                if ($uri === null) {
                    $uri = Str::kebab(Str::plural($reflectionClass->getShortName()));
                }

                // Get the model name for the controller (singular, lowercase)
                $modelNameForController = Str::lower(Str::singular($reflectionClass->getShortName()));

                // Construct the full base URI
                $fullBaseUri = "/{$apiPrefix}/{$version}";                // Register routes using Route facade with authentication middleware
                Route::prefix("{$apiPrefix}/{$version}")
                    ->middleware(['api', 'auth:sanctum'])
                    ->group(function () use ($uri, $modelNameForController) {
                    // Register GET route for collection (index)
                    Route::get("/{$uri}", [ApiResourceController::class, 'index'])
                        ->defaults('modelName', $modelNameForController)
                        ->name("{$modelNameForController}.index");

                    // Register POST route for creating new resource (store)
                    Route::post("/{$uri}", [ApiResourceController::class, 'store'])
                        ->defaults('modelName', $modelNameForController)
                        ->name("{$modelNameForController}.store");                    // Register GET route for schema (metadata) - MUST be before parameterized routes
                    Route::get("/{$uri}/schema", [ApiResourceController::class, 'schema'])
                        ->defaults('modelName', $modelNameForController)
                        ->name("{$modelNameForController}.schema");

                    // Register GET route for columns (index configuration) - MUST be before parameterized routes
                    Route::get("/{$uri}/columns", [ApiResourceController::class, 'columns'])
                        ->defaults('modelName', $modelNameForController)
                        ->name("{$modelNameForController}.columns");// Register GET route for single item (show)
                    Route::get("/{$uri}/{id}", [ApiResourceController::class, 'show'])
                        ->defaults('modelName', $modelNameForController)
                        ->name("{$modelNameForController}.show");

                    // Register PUT/PATCH route for updating resource (update)
                    Route::put("/{$uri}/{id}", [ApiResourceController::class, 'update'])
                        ->defaults('modelName', $modelNameForController)
                        ->name("{$modelNameForController}.update");
                    
                    Route::patch("/{$uri}/{id}", [ApiResourceController::class, 'update'])
                        ->defaults('modelName', $modelNameForController)
                        ->name("{$modelNameForController}.patch");                    // Register DELETE route for deleting resource (destroy)
                    Route::delete("/{$uri}/{id}", [ApiResourceController::class, 'destroy'])
                        ->defaults('modelName', $modelNameForController)
                        ->name("{$modelNameForController}.destroy");
                });
                // Log successful registration
                /*
                Log::info("API Resource registered: {$className}", [
                    'routes' => [
                        'index' => "GET {$fullBaseUri}/{$uri}",
                        'store' => "POST {$fullBaseUri}/{$uri}",
                        'schema' => "GET {$fullBaseUri}/{$uri}/schema",
                        'columns' => "GET {$fullBaseUri}/{$uri}/columns",
                        'show' => "GET {$fullBaseUri}/{$uri}/{id}",
                        'update' => "PUT/PATCH {$fullBaseUri}/{$uri}/{id}",
                        'destroy' => "DELETE {$fullBaseUri}/{$uri}/{id}"
                    ],
                    'controller_model_name' => $modelNameForController
                ]);
                */

            } catch (ReflectionException $e) {
                Log::warning("Failed to reflect class from file: {$file->getFilename()}", [
                    'error' => $e->getMessage(),
                    'file' => $file->getPathname()
                ]);
            } catch (\Exception $e) {
                Log::error("Error processing model file: {$file->getFilename()}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
}
