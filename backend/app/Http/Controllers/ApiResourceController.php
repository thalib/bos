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
                    'NOT_FOUND',
                    'Resource endpoint not found',
                    404
                );
            }

            // Convert model name to proper class name (e.g., 'user' -> 'User', 'user-profiles' -> 'UserProfile')
            $className = Str::studly(Str::singular($modelName));
            $modelClass = "App\\Models\\{$className}";            // Validate that the class exists and is a Model
            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                return $this->errorResponse(
                    'NOT_FOUND',
                    'Resource not found',
                    404
                );
            }            // Build query with search and sorting
            $query = $modelClass::query();

            // Create model instance for introspection
            $model = new $modelClass;

            // Apply search filtering using the filter service
            $searchErrors = $this->applySearchWithValidation($query, $request, $model);
            if (! empty($searchErrors)) {
                return $this->errorResponse(
                    $searchErrors['error'],
                    $searchErrors['message'],
                    $searchErrors['status'],
                    $searchErrors['details'] ?? []
                );
            }

            // Apply filters based on model's getApiFilters or fallback to default active filter
            $appliedFilters = [];
            $filteringErrors = $this->applyFiltersWithValidation($query, $request, $model, $appliedFilters);
            if (! empty($filteringErrors)) {
                return $this->errorResponse(
                    $filteringErrors['error'],
                    $filteringErrors['message'],
                    $filteringErrors['status'],
                    $filteringErrors['details'] ?? []
                );
            }

            // Apply sorting if sort parameter is present
            $sortingErrors = $this->applySorting($query, $request, $model);
            if (! empty($sortingErrors)) {
                return $this->errorResponse(
                    $sortingErrors['error'],
                    $sortingErrors['message'],
                    $sortingErrors['status'],
                    $sortingErrors['details'] ?? []
                );
            }

            // Prepare metadata
            $metadata = $this->metadataService->buildResponseMetadata($request, $query, $appliedFilters);

            // Validate pagination parameters
            $validationErrors = $this->validatePaginationParameters($request);
            if (! empty($validationErrors)) {
                return $this->errorResponse(
                    'INVALID_PARAMETERS',
                    'Invalid pagination parameters',
                    400,
                    $validationErrors
                );
            }

            // Always return paginated results for index requests
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);
            $paginatedResults = $query->paginate($perPage, ['*'], 'page', $page);

            return $this->paginatedResponse($paginatedResults, $metadata, 'Resources retrieved successfully');
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
                    'NOT_FOUND',
                    'Resource endpoint not found',
                    404
                );
            }

            // Convert model name to proper class name (e.g., 'user' -> 'User', 'user-profiles' -> 'UserProfile')
            $className = Str::studly(Str::singular($modelName));
            $modelClass = "App\\Models\\{$className}";

            // Validate that the class exists and is a Model
            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                return $this->errorResponse(
                    'NOT_FOUND',
                    'Resource not found',
                    404
                );
            }

            // Find the specific item by ID (throws ModelNotFoundException if not found)
            $item = $modelClass::findOrFail($id);

            return $this->successResponse($item, 'Resource retrieved successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                'NOT_FOUND',
                'Resource not found',
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
                    'NOT_FOUND',
                    'Resource endpoint not found',
                    404
                );
            }

            // Validate that the class exists and is a Model
            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                $className = class_basename($modelClass);

                return $this->errorResponse(
                    'NOT_FOUND',
                    'Resource not found',
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
                'INTERNAL_SERVER_ERROR',
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

            // Check if this is a validation-type error that should return 422
            $validationErrors = ['required_field_missing', 'unique_constraint_violation', 'invalid_data_type'];
            $isValidationError = isset($specificError['error_type']) &&
                in_array($specificError['error_type'], $validationErrors);

            if ($isValidationError) {
                return $this->errorResponse(
                    'VALIDATION_FAILED',
                    'The given data was invalid',
                    422,
                    $specificError
                );
            }

            return $this->errorResponse(
                'INTERNAL_SERVER_ERROR',
                'An error occurred while creating the resource',
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
                'VALIDATION_FAILED',
                'Invalid data provided',
                422,
                [
                    'error_type' => 'mass_assignment_protection',
                    'attempted_data' => $validated ?? [],
                    'model' => $modelName,
                ]
            );
        } catch (\TypeError $e) {
            // Type errors (wrong data types passed to model attributes)
            Log::error("Type error creating {$modelName} resource", [
                'error' => $e->getMessage(),                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'INTERNAL_SERVER_ERROR',
                'An error occurred while creating the resource',
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
                'NOT_FOUND',
                'Resource not found',
                404
            );
        } catch (ValidationException $e) {
            return $this->errorResponse(
                'INTERNAL_SERVER_ERROR',
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
                    'NOT_FOUND',
                    'Resource endpoint not found',
                    404
                );
            }

            // Convert model name to proper class name
            $className = Str::studly(Str::singular($modelName));
            $modelClass = "App\\Models\\{$className}";

            // Validate that the class exists and is a Model
            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                return $this->errorResponse(
                    'NOT_FOUND',
                    'Resource not found',
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
                'NOT_FOUND',
                'Resource not found',
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
     * Apply search with validation and return errors if any.
     */
    protected function applySearchWithValidation($query, Request $request, Model $model): array
    {
        // Validate search parameter
        $searchValidation = $this->validateSearchParameter($request);
        if (! empty($searchValidation)) {
            return $searchValidation;
        }

        // Apply search if validation passes
        $this->applySearch($query, $request, $model);

        return [];
    }

    /**
     * Validate search parameter.
     */
    protected function validateSearchParameter(Request $request): array
    {
        if ($request->has('search')) {
            $searchTerm = $request->input('search');

            // Check if search term is not empty and meets minimum length requirement
            if (! empty($searchTerm) && strlen(trim($searchTerm)) < 2) {
                return [
                    'error' => 'INVALID_PARAMETERS',
                    'message' => 'Search term must be at least 2 characters long.',
                    'status' => 400,
                    'details' => [
                        'search_term' => $searchTerm,
                        'current_length' => strlen(trim($searchTerm)),
                        'minimum_length' => 2,
                    ],
                ];
            }
        }

        return []; // No validation errors
    }

    /**
     * Apply filters with validation and return errors if any.
     */
    protected function applyFiltersWithValidation($query, Request $request, Model $model, &$appliedFilters = []): array
    {
        try {
            $appliedFilters = $this->applyFilters($query, $request, $model);

            return [];
        } catch (\InvalidArgumentException $e) {
            return [
                'error' => 'INVALID_PARAMETERS',
                'message' => $e->getMessage(),
                'status' => 400,
                'details' => [
                    'exception' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Validate filter parameter format and field validity.
     */
    protected function validateFilterParameter(string $filterParam, Model $model): array
    {
        // Check if filter has correct format (field:value)
        if (strpos($filterParam, ':') === false) {
            return [
                'error' => 'INVALID_PARAMETERS',
                'message' => 'Invalid filter format. Expected format: field:value',
                'status' => 400,
                'details' => [
                    'filter_param' => $filterParam,
                    'expected_format' => 'field:value',
                ],
            ];
        }

        // Parse field:value
        [$field, $value] = explode(':', $filterParam, 2);
        $field = trim($field);
        $value = trim($value);

        // Check if model has getApiFilters method
        if (method_exists($model, 'getApiFilters')) {
            $availableFilters = $model->getApiFilters();

            // Validate that the field is in available filters
            if (! array_key_exists($field, $availableFilters)) {
                $availableFields = array_keys($availableFilters);

                return [
                    'error' => 'INVALID_PARAMETERS',
                    'message' => "Invalid filter field '{$field}'. Available fields: ".implode(', ', $availableFields),
                    'status' => 400,
                    'details' => [
                        'filter_field' => $field,
                        'available_fields' => $availableFields,
                    ],
                ];
            }

            // Validate that the value is in allowed values (if specified)
            if (isset($availableFilters[$field]['values']) && ! empty($availableFilters[$field]['values'])) {
                if (! in_array($value, $availableFilters[$field]['values'])) {
                    $allowedValues = $availableFilters[$field]['values'];

                    return [
                        'error' => 'INVALID_PARAMETERS',
                        'message' => "Invalid filter value '{$value}' for field '{$field}'. Allowed values: ".implode(', ', $allowedValues),
                        'status' => 400,
                        'details' => [
                            'filter_field' => $field,
                            'filter_value' => $value,
                            'allowed_values' => $allowedValues,
                        ],
                    ];
                }
            }
        } else {
            // If model doesn't have getApiFilters, reject any filter attempts
            return [
                'error' => 'INVALID_PARAMETERS',
                'message' => 'Filtering is not supported for this resource',
                'status' => 400,
                'details' => [
                    'filter_field' => $field,
                    'resource' => class_basename($model),
                ],
            ];
        }

        return []; // No validation errors
    }

    /**
     * Apply filters based on model's getApiFilters method or fallback to default active filter.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applyFilters($query, Request $request, Model $model): array
    {
        $appliedFilters = [];

        // Handle single filter parameter with field:value format
        if ($request->has('filter')) {
            $filterParam = $request->input('filter');

            // Validate filter format
            $filterValidation = $this->validateFilterParameter($filterParam, $model);
            if (! empty($filterValidation)) {
                throw new \InvalidArgumentException($filterValidation['message']);
            }

            // Parse field:value format
            if (strpos($filterParam, ':') !== false) {
                [$field, $value] = explode(':', $filterParam, 2);
                $field = trim($field);
                $value = trim($value);

                // Apply the filter
                $this->applyFilterToQuery($query, $field, $value, $model);
                $appliedFilters[$field] = [
                    'field' => $field,
                    'value' => $value,
                ];
            }
        }

        // Get available filters from model (for individual parameter support)
        $availableFilters = [];
        if (method_exists($model, 'getApiFilters')) {
            $availableFilters = $model->getApiFilters();
        }

        // If model has API filters, also check for individual filter parameters
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

    /**
     * Validate pagination parameters.
     */
    protected function validatePaginationParameters(Request $request): array
    {
        $errors = [];

        // Validate per_page parameter
        if ($request->has('per_page')) {
            $perPage = $request->input('per_page');

            // Check if it's numeric
            if (! is_numeric($perPage)) {
                $errors['per_page'] = 'The per_page parameter must be a number.';
            } else {
                $perPage = (int) $perPage;

                // Check if it's within valid range (1-100)
                if ($perPage <= 0) {
                    $errors['per_page'] = 'The per_page parameter must be greater than 0.';
                } elseif ($perPage > 100) {
                    $errors['per_page'] = 'The per_page parameter cannot be greater than 100.';
                }
            }
        }

        // Validate page parameter
        if ($request->has('page')) {
            $page = $request->input('page');

            // Check if it's numeric
            if (! is_numeric($page)) {
                $errors['page'] = 'The page parameter must be a number.';
            } else {
                $page = (int) $page;

                // Check if it's a positive number
                if ($page <= 0) {
                    $errors['page'] = 'The page parameter must be greater than 0.';
                }
            }
        }

        return $errors;
    }

    /**
     * Apply sorting to the query based on request parameters.
     * Returns validation errors if invalid sort parameters are provided.
     */
    protected function applySorting($query, Request $request, Model $model): array
    {
        if (! $request->has('sort')) {
            return [];
        }

        $sortField = trim($request->input('sort'));
        $direction = trim($request->input('dir', 'asc'));

        // Validate sort direction
        if (! in_array(strtolower($direction), ['asc', 'desc'])) {
            return [
                'error' => 'INVALID_PARAMETERS',
                'message' => 'Invalid sort direction. Must be "asc" or "desc".',
                'status' => 400,
                'details' => [
                    'sort_direction' => $direction,
                    'allowed_directions' => ['asc', 'desc'],
                ],
            ];
        }

        // Get sortable columns from the model
        $sortableColumns = $this->getSortableColumns($model);

        // Validate sort field - must be in sortable columns
        if (! in_array($sortField, $sortableColumns)) {
            return [
                'error' => 'INVALID_PARAMETERS',
                'message' => "Invalid sort column '{$sortField}'. Allowed columns: ".implode(', ', $sortableColumns),
                'status' => 400,
                'details' => [
                    'sort_column' => $sortField,
                    'allowed_columns' => $sortableColumns,
                ],
            ];
        }

        // Apply the sorting
        $query->orderBy($sortField, strtolower($direction));

        return [];
    }

    /**
     * Get sortable columns from the model's getIndexColumns method.
     */
    protected function getSortableColumns(Model $model): array
    {
        $sortableColumns = ['id', 'created_at', 'updated_at']; // Always allow these

        if (method_exists($model, 'getIndexColumns')) {
            $indexColumns = $model->getIndexColumns();

            foreach ($indexColumns as $column) {
                if (isset($column['sortable']) && $column['sortable'] === true && isset($column['field'])) {
                    $sortableColumns[] = $column['field'];
                }
            }
        }

        return array_unique($sortableColumns);
    }
}
