<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Http\Responses\ApiResponseTrait;
use App\Services\DatabaseErrorParser;
use App\Services\ResourceFilterService;
use App\Services\ResourceLogger;
use App\Services\ResourceMetadataService;
use App\Services\ResourcePaginationService;
use App\Services\ResourceSearchService;
use App\Services\ResourceSortingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * API Resource Controller - CRUD Operations Only
 *
 * RULES FOR FUTURE MODIFICATIONS:
 * 1. This controller MUST contain ONLY the 5 basic CRUD methods:
 *    - index(), store(), show(), update(), destroy()
 * 2. NO helper methods, validation logic, or business logic allowed
 * 3. All query processing MUST use dedicated service classes:
 *    - ResourceSearchService for search functionality
 *    - ResourceFilterService for resource filtering
 *    - ResourcePaginationService for pagination logic
 *    - ResourceSortingService for sorting logic
 * 4. Response formatting MUST use ApiResponseTrait only
 * 5. Any additional logic MUST be extracted to appropriate service classes
 * 6. This controller should remain under 200 lines total
 *
 * @see App\Services\ResourceSearchService
 * @see App\Services\ResourceFilterService
 * @see App\Services\ResourcePaginationService
 * @see App\Services\ResourceSortingService
 */
class ApiResourceController extends Controller
{
    use ApiResponseTrait;

    protected ResourceMetadataService $metadataService;

    protected ResourceSearchService $searchService;

    protected ResourceFilterService $filterService;

    protected ResourcePaginationService $paginationService;

    protected ResourceSortingService $sortingService;

    public function __construct(
        ResourceMetadataService $metadataService,
        ResourceSearchService $searchService,
        ResourceFilterService $filterService,
        ResourcePaginationService $paginationService,
        ResourceSortingService $sortingService
    ) {
        $this->metadataService = $metadataService;
        $this->searchService = $searchService;
        $this->filterService = $filterService;
        $this->paginationService = $paginationService;
        $this->sortingService = $sortingService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $modelName = null;
        try {
            // 1. Model resolution and validation
            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? null;

            if (! $modelName) {
                return $this->errorResponse('NOT_FOUND', 'Resource endpoint not found', 404);
            }

            $className = Str::studly(Str::singular($modelName));
            $modelClass = "App\\Models\\{$className}";

            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                return $this->errorResponse('NOT_FOUND', 'Resource not found', 404);
            }

            $query = $modelClass::query();
            $model = new $modelClass;
            $notifications = [];

            // 2. Apply search via ResourceSearchService
            $searchResult = $this->searchService->applySearchFilters($query, $request, $model);
            if (isset($searchResult['notifications'])) {
                $notifications = array_merge($notifications, $searchResult['notifications']);
            }

            // 3. Apply filters via ResourceFilterService
            $appliedFilters = [];
            $filterResult = $this->filterService->applyResourceFilters($query, $request, $model);
            if (isset($filterResult['notifications'])) {
                $notifications = array_merge($notifications, $filterResult['notifications']);
            }

            // 4. Apply sorting via ResourceSortingService
            $sortingResult = $this->sortingService->processSorting($request, $model);
            if (isset($sortingResult['notifications'])) {
                $notifications = array_merge($notifications, $sortingResult['notifications']);
            }

            // Only apply sorting if sort parameter is provided
            if ($request->has('sort')) {
                $query->orderBy($sortingResult['sortBy'], $sortingResult['sortOrder']);
            }

            // 5. Apply pagination via ResourcePaginationService
            // Count items AFTER filters and search are applied
            $totalItems = $query->count();
            $paginationResult = $this->paginationService->processPagination($request, $totalItems);
            if (isset($paginationResult['notifications'])) {
                $notifications = array_merge($notifications, $paginationResult['notifications']);
            }

            // 6. Handle pagination safely - use fallback values if pagination service fails
            $perPage = $paginationResult['perPage'] ?? 20;
            $page = $paginationResult['page'] ?? 1;
            $requestedPage = $paginationResult['originalPage'] ?? $page;

            // Ensure we don't exceed available pages
            $maxPages = $totalItems > 0 ? (int) ceil($totalItems / $perPage) : 1;
            if ($page > $maxPages) {
                $page = $maxPages;
                // Add notification about page adjustment only if we actually adjusted the page
                if ($requestedPage > $maxPages) {
                    $notifications[] = [
                        'type' => 'warning',
                        'message' => "Requested page {$requestedPage} exceeds available pages. Showing page {$page} instead.",
                        'field' => 'page',
                    ];
                }
            }

            // 7. Return paginated response with notifications
            $paginatedResults = $query->paginate($perPage, ['*'], 'page', $page);
            $metadata = $this->metadataService->buildResponseMetadata($request, $query, $appliedFilters);

            // Override sort metadata with validated parameters from sorting service
            if ($request->has('sort')) {
                $metadata['sort'] = [
                    'column' => $sortingResult['sortBy'],
                    'dir' => $sortingResult['sortOrder'],
                ];
            }

            // Ensure notifications is always an array or null, never empty array
            $finalNotifications = ! empty($notifications) ? $notifications : null;

            // Include notifications in metadata
            if ($finalNotifications !== null) {
                $metadata['notifications'] = $finalNotifications;
            }

            return $this->paginatedResponse($paginatedResults, $metadata, 'Resources retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error fetching '.($modelName ?? 'unknown model').' resources', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('INTERNAL_SERVER_ERROR', 'An error occurred while fetching the resources', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $modelName = null;
        try {
            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? null;

            if (! $modelName) {
                return $this->errorResponse('NOT_FOUND', 'Resource endpoint not found', 404);
            }

            $className = Str::studly(Str::singular($modelName));
            $modelClass = "App\\Models\\{$className}";

            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                return $this->errorResponse('NOT_FOUND', 'Resource not found', 404);
            }

            $item = $modelClass::findOrFail($id);

            return $this->successResponse($item, 'Resource retrieved successfully');

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('NOT_FOUND', 'Resource not found', 404);
        } catch (\Exception $e) {
            Log::error('Error fetching '.($modelName ?? 'unknown model')." resource with ID {$id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('INTERNAL_SERVER_ERROR', 'An error occurred while fetching the resource', 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreResourceRequest $request): JsonResponse
    {
        $modelName = null;
        $validated = null;
        try {
            $modelClass = $request->getModelClass();

            if (! $modelClass) {
                return $this->errorResponse('NOT_FOUND', 'Resource endpoint not found', 404);
            }

            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                return $this->errorResponse('NOT_FOUND', 'Resource not found', 404);
            }

            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? class_basename($modelClass);
            $validated = $request->validated();

            $model = new $modelClass;
            if (method_exists($model, 'applyDatabaseDefaults')) {
                $originalData = $validated;
                $validated = $model->applyDatabaseDefaults($validated, false);
                ResourceLogger::logDefaultsApplication($originalData, $validated, 'create', $modelName);
            }

            $resource = $modelClass::create($validated);

            return $this->successResponse($resource, 'Resource created successfully', 201);

        } catch (ValidationException $e) {
            return $this->errorResponse('INTERNAL_SERVER_ERROR', 'The given data was invalid', 422, [], $e->errors());
        } catch (\Illuminate\Database\QueryException $e) {
            // Database-specific errors (foreign key constraints, data type mismatches, etc.)
            $errorCode = $e->errorInfo[1] ?? null;
            $errorMessage = $e->getMessage();

            Log::error("Database error creating {$modelName} resource", [
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            $specificError = DatabaseErrorParser::parse($e, $validated ?? []);
            $validationErrors = ['required_field_missing', 'unique_constraint_violation', 'invalid_data_type'];
            $isValidationError = isset($specificError['error_type']) && in_array($specificError['error_type'], $validationErrors);

            if ($isValidationError) {
                return $this->errorResponse('VALIDATION_FAILED', 'The given data was invalid', 422, $specificError);
            }

            return $this->errorResponse('INTERNAL_SERVER_ERROR', 'An error occurred while creating the resource', 500, array_merge($specificError, [
                'model' => $modelName,
                'error_code' => $errorCode,
                'failed_data' => $validated ?? [],
            ]));
        } catch (\Exception $e) {
            // Generic fallback for any other errors
            Log::error("Unexpected error creating {$modelName} resource", [
                'error_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('INTERNAL_SERVER_ERROR', 'An unexpected error occurred while creating the resource', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResourceRequest $request, string $id): JsonResponse
    {
        $modelName = null;
        $validated = null;

        try {
            $modelClass = $request->getModelClass();

            if (! $modelClass) {
                return $this->errorResponse('NOT_FOUND', 'Resource endpoint not found', 404);
            }

            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                return $this->errorResponse('NOT_FOUND', 'Resource not found', 404);
            }

            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? class_basename($modelClass);
            $resource = $modelClass::findOrFail($id);
            $validated = $request->validated();

            if (method_exists($resource, 'applyDatabaseDefaults')) {
                $originalData = $validated;
                $validated = $resource->applyDatabaseDefaults($validated, true);
                ResourceLogger::logDefaultsApplication($originalData, $validated, 'update', $modelName);
            }

            $resource->update($validated);
            $resource->refresh();

            return $this->successResponse($resource, 'Resource updated successfully');

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('NOT_FOUND', 'Resource not found', 404);
        } catch (ValidationException $e) {
            return $this->errorResponse('INTERNAL_SERVER_ERROR', 'The given data was invalid', 422, [], $e->errors());
        } catch (\Illuminate\Database\QueryException $e) {
            // Database-specific errors (foreign key constraints, data type mismatches, etc.)
            $errorCode = $e->errorInfo[1] ?? null;
            $errorMessage = $e->getMessage();

            Log::error("Database error updating {$modelName} resource with ID {$id}", [
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            $specificError = DatabaseErrorParser::parse($e, $validated ?? []);

            return $this->errorResponse('INTERNAL_SERVER_ERROR', 'Database operation failed while updating the resource', 500, $specificError);
        } catch (\Exception $e) {
            // Generic fallback for any other errors
            Log::error("Unexpected error updating {$modelName} resource with ID {$id}", [
                'error_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('INTERNAL_SERVER_ERROR', 'An unexpected error occurred while updating the resource', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $modelName = null;

        try {
            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? null;

            if (! $modelName) {
                return $this->errorResponse('NOT_FOUND', 'Resource endpoint not found', 404);
            }

            $className = Str::studly(Str::singular($modelName));
            $modelClass = "App\\Models\\{$className}";

            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                return $this->errorResponse('NOT_FOUND', 'Resource not found', 404);
            }

            $resource = $modelClass::findOrFail($id);
            $resource->delete();

            return $this->successResponse(null, 'Resource deleted successfully', 204);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('NOT_FOUND', 'Resource not found', 404);
        } catch (\Exception $e) {
            Log::error('Error deleting '.($modelName ?? 'unknown model')." resource with ID {$id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('INTERNAL_SERVER_ERROR', 'An error occurred while deleting the resource', 500);
        }
    }
}
