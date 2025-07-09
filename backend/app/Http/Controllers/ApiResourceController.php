<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Http\Responses\ApiResponseTrait;
use App\Services\DatabaseErrorParser;
use App\Services\ResourceLogger;
use App\Services\ResourceMetadataService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApiResourceController extends Controller
{
    use ApiResponseTrait;

    protected ResourceMetadataService $metadataService;

    public function __construct(ResourceMetadataService $metadataService)
    {
        $this->metadataService = $metadataService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $modelName = null;
        try {
            // Get model name from route defaults
            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? null;

            // Validate that we have a model name
            if (! $modelName) {
                return $this->errorResponse(
                    'BAD_REQUEST',
                    'Model name not found in route defaults',
                    400
                );
            }

            // Convert model name to proper class name (e.g., 'user' -> 'User', 'user-profiles' -> 'UserProfile')
            $className = Str::studly(Str::singular($modelName));
            $modelClass = "App\\Models\\{$className}";            // Validate that the class exists and is a Model
            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                return $this->errorResponse(
                    'RESOURCE_NOT_FOUND',
                    "Model '{$className}' not found or is not a valid Eloquent model",
                    404
                );
            }            // Build query with search and sorting
            $query = $modelClass::query();

            // Create model instance for introspection
            $model = new $modelClass;

            // Apply search filtering using the filter service
            $this->applySearch($query, $request, $model);

            // Apply filters based on model's getApiFilters or fallback to default active filter
            $appliedFilters = $this->applyFilters($query, $request, $model);

            // Apply sorting if sort parameter is present
            if ($request->has('sort')) {
                $sortData = $request->input('sort');

                // Handle the new sort format: {"column": "field_name", "dir": "asc|desc"}
                // Silently ignore invalid sort parameters instead of returning errors
                if (is_array($sortData) && isset($sortData['column'])) {
                    $sortField = trim($sortData['column']);
                    $direction = isset($sortData['dir']) ? trim($sortData['dir']) : 'asc';

                    // Create model instance to get available fields for validation
                    $model = new $modelClass;
                    $fillableFields = $model->getFillable();
                    $commonFields = ['id', 'created_at', 'updated_at'];
                    $allowedSortFields = array_merge($fillableFields, $commonFields);

                    // Validate sort field - if invalid, silently ignore the sort
                    if (! in_array($sortField, $allowedSortFields)) {
                        // Silently ignore invalid sort field, continue without sorting
                    } else {
                        // Validate direction - if invalid, default to 'asc'
                        if (! in_array(strtolower($direction), ['asc', 'desc'])) {
                            $direction = 'asc';
                        }

                        $query->orderBy($sortField, $direction);
                    }
                }
                // Silently ignore invalid sort format (not an object or missing column)
            }

            // Prepare metadata
            $metadata = $this->metadataService->buildResponseMetadata($request, $query, $appliedFilters);

            // Always return paginated results for index requests
            $perPage = $request->input('per_page', 15);
            $paginatedResults = $query->paginate($perPage);

            return $this->paginatedResponse($paginatedResults, $metadata);
        } catch (\Exception $e) {
            Log::error('Error fetching '.($modelName ?? 'unknown model').' resources', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'INTERNAL_SERVER_ERROR',
                'An error occurred while fetching the resources',
                500
            );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id  The resource ID
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $modelName = null;
        try {
            // Get model name from route defaults
            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? null;

            // Validate that we have a model name
            if (! $modelName) {
                return $this->errorResponse(
                    'BAD_REQUEST',
                    'Model name not found in route defaults',
                    400
                );
            }

            // Convert model name to proper class name (e.g., 'user' -> 'User', 'user-profiles' -> 'UserProfile')
            $className = Str::studly(Str::singular($modelName));
            $modelClass = "App\\Models\\{$className}";

            // Validate that the class exists and is a Model
            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                return $this->errorResponse(
                    'RESOURCE_NOT_FOUND',
                    "Model '{$className}' not found or is not a valid Eloquent model",
                    404
                );
            }

            // Find the specific item by ID (throws ModelNotFoundException if not found)
            $item = $modelClass::findOrFail($id);

            return $this->successResponse($item);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                'RESOURCE_NOT_FOUND',
                "Resource with ID '{$id}' not found",
                404
            );
        } catch (\Exception $e) {
            Log::error('Error fetching '.($modelName ?? 'unknown model')." resource with ID {$id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'INTERNAL_SERVER_ERROR',
                'An error occurred while fetching the resource',
                500
            );
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
            // Get the model class from the request
            $modelClass = $request->getModelClass();

            if (! $modelClass) {
                return $this->errorResponse(
                    'BAD_REQUEST',
                    'Model name not found in route defaults',
                    400
                );
            }

            // Validate that the class exists and is a Model
            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                $className = class_basename($modelClass);

                return $this->errorResponse(
                    'RESOURCE_NOT_FOUND',
                    "Model '{$className}' not found or is not a valid Eloquent model",
                    404
                );
            }

            // Get model name for error reporting
            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? class_basename($modelClass);            // Get validated data (password hashing is handled in the request)
            $validated = $request->validated();

            // Apply database defaults for null/empty values
            $model = new $modelClass;
            if (method_exists($model, 'applyDatabaseDefaults')) {
                $originalData = $validated;
                $validated = $model->applyDatabaseDefaults($validated, false);

                // Log defaults application for debugging
                ResourceLogger::logDefaultsApplication($originalData, $validated, 'create', $modelName);
            }

            // Create the resource
            $resource = $modelClass::create($validated);

            return $this->successResponse($resource, 'Resource created successfully', 201);
        } catch (ValidationException $e) {
            return $this->errorResponse(
                'VALIDATION_FAILED',
                'The given data was invalid',
                422,
                [],
                $e->errors()
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // Database-specific errors (foreign key constraints, data type mismatches, etc.)
            $errorCode = $e->errorInfo[1] ?? null;
            $errorMessage = $e->getMessage();

            // Log detailed error for developers
            Log::error("Database error creating {$modelName} resource", [
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Extract specific database error information
            $specificError = DatabaseErrorParser::parse($e, $validated ?? []);

            return $this->errorResponse(
                'DATABASE_ERROR',
                'Database operation failed while creating the resource',
                500,
                array_merge($specificError, [
                    'model' => $modelName,
                    'error_code' => $errorCode,
                    'failed_data' => $validated ?? [],
                ])
            );
        } catch (\Illuminate\Database\Eloquent\MassAssignmentException $e) {
            // Mass assignment protection error
            Log::error("Mass assignment error creating {$modelName} resource", [
                'error' => $e->getMessage(),
                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'MASS_ASSIGNMENT_ERROR',
                'One or more fields are not allowed to be mass assigned',
                500,
                [
                    'error_type' => 'mass_assignment_protection',
                    'attempted_data' => $validated ?? [],
                    'model' => $modelName,
                    'error_message' => $e->getMessage(),
                ]
            );
        } catch (\TypeError $e) {
            // Type errors (wrong data types passed to model attributes)
            Log::error("Type error creating {$modelName} resource", [
                'error' => $e->getMessage(),                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'TYPE_ERROR',
                'Invalid data type provided for one or more fields',
                500,
                [
                    'error_type' => 'type_mismatch',
                    'error_message' => $e->getMessage(),
                    'attempted_data' => $validated ?? [],
                    'model' => $modelName,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
        } catch (\Exception $e) {
            // Generic fallback for any other errors
            Log::error("Unexpected error creating {$modelName} resource", [
                'error_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'INTERNAL_SERVER_ERROR',
                'An unexpected error occurred while creating the resource',
                500,
                [
                    'error_type' => 'unexpected_exception',
                    'error_class' => get_class($e),
                    'error_message' => $e->getMessage(),
                    'model' => $modelName,
                    'attempted_data' => $validated ?? [],
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  string  $id  The resource ID
     */
    public function update(UpdateResourceRequest $request, string $id): JsonResponse
    {
        $modelName = null;
        $validated = null;

        try {
            // Get the model class from the request
            $modelClass = $request->getModelClass();

            if (! $modelClass) {
                return response()->json([
                    'error' => 'Bad Request',
                    'message' => 'Model name not found in route defaults',
                ], 400);
            }

            // Validate that the class exists and is a Model
            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                $className = class_basename($modelClass);

                return response()->json([
                    'error' => 'Resource not found',
                    'message' => "Model '{$className}' not found or is not a valid Eloquent model",
                ], 404);
            }

            // Get model name for error reporting
            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? class_basename($modelClass);

            // Find the resource
            $resource = $modelClass::findOrFail($id);            // Get validated data (password hashing is handled in the request)
            $validated = $request->validated();

            // Apply database defaults for null/empty values (for update operations)
            if (method_exists($resource, 'applyDatabaseDefaults')) {
                $originalData = $validated;
                $validated = $resource->applyDatabaseDefaults($validated, true);

                // Log defaults application for debugging
                ResourceLogger::logDefaultsApplication($originalData, $validated, 'update', $modelName);
            }            // Update the resource
            $resource->update($validated);

            // Refresh the resource to get the latest state
            $resource->refresh();

            return $this->successResponse($resource, 'Resource updated successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                'RESOURCE_NOT_FOUND',
                "Resource with ID '{$id}' not found",
                404
            );
        } catch (ValidationException $e) {
            return $this->errorResponse(
                'VALIDATION_FAILED',
                'The given data was invalid',
                422,
                [],
                $e->errors()
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // Database-specific errors (foreign key constraints, data type mismatches, etc.)
            $errorCode = $e->errorInfo[1] ?? null;
            $errorMessage = $e->getMessage();

            // Log detailed error for developers
            Log::error("Database error updating {$modelName} resource with ID {$id}", [
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Extract specific database error information
            $specificError = DatabaseErrorParser::parse($e, $validated ?? []);

            return response()->json([
                'error' => 'Database error',
                'message' => 'Database operation failed while updating the resource',
                'details' => $specificError,
                'debug_info' => [
                    'model' => $modelName,
                    'resource_id' => $id,
                    'error_code' => $errorCode,
                    'failed_data' => $validated ?? [],
                ],
            ], 500);
        } catch (\Illuminate\Database\Eloquent\MassAssignmentException $e) {
            // Mass assignment protection error
            Log::error("Mass assignment error updating {$modelName} resource with ID {$id}", [
                'error' => $e->getMessage(),
                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Mass assignment error',
                'message' => 'One or more fields are not allowed to be mass assigned',
                'details' => [
                    'error_type' => 'mass_assignment_protection',
                    'attempted_data' => $validated ?? [],
                ],
                'debug_info' => [
                    'model' => $modelName,
                    'resource_id' => $id,
                    'error_message' => $e->getMessage(),
                ],
            ], 500);
        } catch (\TypeError $e) {
            // Type errors (wrong data types passed to model attributes)
            Log::error("Type error updating {$modelName} resource with ID {$id}", [
                'error' => $e->getMessage(),
                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Data type error',
                'message' => 'Invalid data type provided for one or more fields',
                'details' => [
                    'error_type' => 'type_mismatch',
                    'error_message' => $e->getMessage(),
                    'attempted_data' => $validated ?? [],
                ],
                'debug_info' => [
                    'model' => $modelName,
                    'resource_id' => $id,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 500);
        } catch (\Exception $e) {
            // Generic fallback for any other errors
            Log::error("Unexpected error updating {$modelName} resource with ID {$id}", [
                'error_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred while updating the resource',
                'details' => [
                    'error_type' => 'unexpected_exception',
                    'error_class' => get_class($e),
                    'error_message' => $e->getMessage(),
                ],
                'debug_info' => [
                    'model' => $modelName,
                    'resource_id' => $id,
                    'attempted_data' => $validated ?? [],
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id  The resource ID
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $modelName = null;

        try {
            // Get model name from route defaults
            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? null;

            // Validate that we have a model name
            if (! $modelName) {
                return $this->errorResponse(
                    'BAD_REQUEST',
                    'Model name not found in route defaults',
                    400
                );
            }

            // Convert model name to proper class name
            $className = Str::studly(Str::singular($modelName));
            $modelClass = "App\\Models\\{$className}";

            // Validate that the class exists and is a Model
            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                return $this->errorResponse(
                    'RESOURCE_NOT_FOUND',
                    "Model '{$className}' not found or is not a valid Eloquent model",
                    404
                );
            }

            // Find and delete the resource
            $resource = $modelClass::findOrFail($id);
            $resource->delete();

            return response()->json([
                'success' => true,
                'message' => 'Resource deleted successfully',
            ], 204);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                'RESOURCE_NOT_FOUND',
                "Resource with ID '{$id}' not found",
                404
            );
        } catch (\Exception $e) {
            Log::error('Error deleting '.($modelName ?? 'unknown model')." resource with ID {$id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'INTERNAL_SERVER_ERROR',
                'An error occurred while deleting the resource',
                500
            );
        }
    }

    /**
     * Get the form schema for the resource.
     */
    public function schema(Request $request): JsonResponse
    {
        $modelName = null;

        try {
            // Get model name from route defaults
            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? null;            // Validate that we have a model name
            if (! $modelName) {
                return $this->errorResponse(
                    'BAD_REQUEST',
                    'Model name not found in route defaults',
                    400
                );
            }

            // Convert model name to proper class name
            $className = Str::studly(Str::singular($modelName));
            $modelClass = "App\\Models\\{$className}";

            // Validate that the class exists and is a Model
            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                return $this->errorResponse(
                    'RESOURCE_NOT_FOUND',
                    "Model '{$className}' not found or is not a valid Eloquent model",
                    404
                );
            }

            $model = new $modelClass;

            // Use metadata service to get schema data
            $schemaData = $this->metadataService->buildSchemaMetadata($model);

            if ($schemaData === null) {
                return $this->errorResponse(
                    'INTERNAL_SERVER_ERROR',
                    'An error occurred while generating the schema',
                    500
                );
            }

            // Check if schema data is grouped or flat and format accordingly
            if (is_array($schemaData) && ! empty($schemaData) &&
                isset($schemaData[0]) &&
                is_array($schemaData[0]) &&
                isset($schemaData[0]['group']) &&
                isset($schemaData[0]['fields'])) {
                // This is a grouped schema, return it as-is
                return $this->successResponse($schemaData);
            } else {
                // This is a flat schema, wrap it in properties
                $schema = [
                    'properties' => $schemaData,
                ];

                return $this->successResponse($schema);
            }
        } catch (\Exception $e) {
            Log::error('Error generating schema for '.($modelName ?? 'unknown model'), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'INTERNAL_SERVER_ERROR',
                'An error occurred while generating the schema',
                500
            );
        }
    }

    /**
     * Get the index columns configuration for the resource.
     */
    public function columns(Request $request): JsonResponse
    {
        $modelName = null;

        try {
            // Get model name from route defaults
            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? null;            // Validate that we have a model name
            if (! $modelName) {
                return $this->errorResponse(
                    'BAD_REQUEST',
                    'Model name not found in route defaults',
                    400
                );
            }

            // Convert model name to proper class name
            $className = Str::studly(Str::singular($modelName));
            $modelClass = "App\\Models\\{$className}";

            // Validate that the class exists and is a Model
            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                return $this->errorResponse(
                    'RESOURCE_NOT_FOUND',
                    "Model '{$className}' not found or is not a valid Eloquent model",
                    404
                );
            }

            $model = new $modelClass;

            // Use metadata service to get columns data
            $columnsData = $this->metadataService->buildColumnsMetadata($model);

            if ($columnsData === null) {
                return $this->errorResponse(
                    'INTERNAL_SERVER_ERROR',
                    'An error occurred while generating the columns',
                    500
                );
            }

            return $this->successResponse($columnsData);
        } catch (\Exception $e) {
            Log::error('Error generating columns for '.($modelName ?? 'unknown model'), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'INTERNAL_SERVER_ERROR',
                'An error occurred while generating the columns',
                500
            );
        }
    }

    /**
     * Apply search filtering to a query based on request parameters.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applySearch($query, Request $request, Model $model): void
    {
        if ($request->has('search') && ! empty($request->input('search'))) {
            $searchTerm = $request->input('search');
            $searchableFields = $this->getSearchableFields($model);

            if (! empty($searchableFields)) {
                $query->where(function ($q) use ($searchableFields, $searchTerm) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                    }
                });
            }
        }
    }

    /**
     * Apply filters based on model's getApiFilters method or fallback to default active filter.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applyFilters($query, Request $request, Model $model): array
    {
        $appliedFilters = [];

        // Get available filters from model
        $availableFilters = [];
        if (method_exists($model, 'getApiFilters')) {
            $availableFilters = $model->getApiFilters();
        }

        // If model has API filters, apply them
        if (! empty($availableFilters)) {
            foreach ($availableFilters as $field => $config) {
                if ($request->has($field)) {
                    $filterValue = $request->input($field);

                    // Skip empty values
                    if ($filterValue !== null && $filterValue !== '' && $filterValue !== []) {
                        $this->applyFilterToQuery($query, $field, $filterValue, $model);
                        $appliedFilters[$field] = [
                            'field' => $field,
                            'value' => $filterValue,
                        ];
                    }
                }
            }
        } else {
            // Fallback: Apply default 'active' filter if no custom filters defined
            if ($request->has('active') && in_array('active', $model->getFillable())) {
                $activeValue = $request->input('active');
                if ($activeValue !== null && $activeValue !== '') {
                    // Convert string 'true'/'false' to boolean for active filter
                    $boolValue = filter_var($activeValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($boolValue !== null) {
                        $query->where('active', $boolValue);
                        $appliedFilters['active'] = [
                            'field' => 'active',
                            'value' => $boolValue,
                        ];
                    }
                }
            }
        }

        return $appliedFilters;
    }

    /**
     * Apply a specific filter to the query using Eloquent scopes when available.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     */
    protected function applyFilterToQuery($query, string $field, $value, Model $model): void
    {
        // Check if model has a scope for this filter
        $scopeMethod = 'scope'.ucfirst(Str::camel($field));

        if (method_exists($model, $scopeMethod)) {
            // Use the model's scope if available
            $query->{Str::camel($field)}($value);
        } else {
            // Default: Apply direct where clause
            $query->where($field, $value);
        }
    }

    /**
     * Get searchable fields from model.
     */
    protected function getSearchableFields(Model $model): array
    {
        // Check if model has custom searchable fields
        if (method_exists($model, 'getSearchableFields')) {
            return $model->getSearchableFields();
        }

        // Fallback: Auto-detect searchable fields
        $fillable = $model->getFillable();
        $searchableFields = [];

        foreach ($fillable as $field) {
            // Include fields that are likely to contain searchable text
            if (Str::contains(strtolower($field), ['name', 'title', 'description', 'email', 'username', 'slug'])) {
                $searchableFields[] = $field;
            }
        }

        return $searchableFields;
    }
}
