<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApiResourceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $modelName = null;        try {
            // Get model name from route defaults
            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? null;

            // Validate that we have a model name
            if (!$modelName) {
                return $this->errorResponse(
                    'BAD_REQUEST',
                    'Model name not found in route defaults',
                    400
                );
            }

            // Convert model name to proper class name (e.g., 'user' -> 'User', 'user-profiles' -> 'UserProfile')
            $className = Str::studly(Str::singular($modelName));
            $modelClass = "App\\Models\\{$className}";            // Validate that the class exists and is a Model
            if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
                return $this->errorResponse(
                    'RESOURCE_NOT_FOUND',
                    "Model '{$className}' not found or is not a valid Eloquent model",
                    404
                );
            }// Build query with search and sorting
            $query = $modelClass::query();

            // Create model instance for introspection
            $model = new $modelClass();            // Apply search filtering if search parameter is present
            if ($request->has('search') && !empty($request->input('search'))) {
                $searchTerm = $request->input('search');
                $searchableFields = $this->getSearchableFields($model);

                if (!empty($searchableFields)) {
                    $query->where(function ($q) use ($searchableFields, $searchTerm) {
                        foreach ($searchableFields as $field) {
                            $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                        }
                    });
                }
            }

            // Apply active filter if filter parameter is present and model has 'active' field
            if ($request->has('filter') && !empty($request->input('filter'))) {
                $filterValue = $request->input('filter');
                $fillableFields = $model->getFillable();
                
                // Only apply active filter if the model has an 'active' field
                if (in_array('active', $fillableFields)) {
                    switch ($filterValue) {
                        case 'active':
                            $query->where('active', true);
                            break;
                        case 'inactive':
                            $query->where('active', false);
                            break;
                        case 'all':
                        default:
                            // No filter applied - show all records
                            break;
                    }
                }
            }

            // Apply sorting if sort parameter is present
            if ($request->has('sort')) {
                $sortFields = explode(',', $request->input('sort'));
                $directions = explode(',', $request->input('direction', 'asc'));

                // Create model instance to get available fields for validation
                $model = new $modelClass();
                $fillableFields = $model->getFillable();
                $commonFields = ['id', 'created_at', 'updated_at'];
                $allowedSortFields = array_merge($fillableFields, $commonFields);

                foreach ($sortFields as $index => $sortField) {
                    $sortField = trim($sortField);
                    $direction = isset($directions[$index]) ? trim($directions[$index]) : 'asc';                    // Validate sort field
                    if (!in_array($sortField, $allowedSortFields)) {
                        return $this->errorResponse(
                            'INVALID_SORT_FIELD',
                            "Sort field '{$sortField}' is not allowed",
                            400,
                            ['allowed_fields' => $allowedSortFields]
                        );
                    }

                    // Validate direction
                    if (!in_array(strtolower($direction), ['asc', 'desc'])) {
                        return $this->errorResponse(
                            'INVALID_SORT_DIRECTION',
                            "Sort direction '{$direction}' is not allowed",
                            400,
                            ['allowed_directions' => ['asc', 'desc']]
                        );
                    }
                    $query->orderBy($sortField, $direction);
                }
            }

            // Prepare metadata
            $metadata = $this->buildResponseMetadata($request, $query);            // Return paginated results if page parameter is present
            if ($request->has('page')) {
                // Get per_page parameter with fallback to default of 15
                $perPage = $request->input('per_page', 15);
                $paginatedResults = $query->paginate($perPage);

                return $this->paginatedResponse($paginatedResults, $metadata);
            }

            // For simple requests without pagination, return data with metadata
            $data = $query->get();

            // Always return data in consistent format
            return $this->successResponse($data, null, 200, $metadata);
        } catch (\Exception $e) {
            Log::error("Error fetching " . ($modelName ?? 'unknown model') . " resources", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
     * @param Request $request
     * @param string $id The resource ID
     * @return JsonResponse
     */    public function show(Request $request, string $id): JsonResponse
    {
        $modelName = null;
        try {
            // Get model name from route defaults
            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? null;

            // Validate that we have a model name
            if (!$modelName) {
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
            if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
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
            Log::error("Error fetching " . ($modelName ?? 'unknown model') . " resource with ID {$id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
     *
     * @param StoreResourceRequest $request
     * @return JsonResponse
     */
    public function store(StoreResourceRequest $request): JsonResponse
    {
        $modelName = null;
        $validated = null;        try {
            // Get the model class from the request
            $modelClass = $request->getModelClass();

            if (!$modelClass) {
                return $this->errorResponse(
                    'BAD_REQUEST',
                    'Model name not found in route defaults',
                    400
                );
            }

            // Validate that the class exists and is a Model
            if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
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
            $model = new $modelClass();
            if (method_exists($model, 'applyDatabaseDefaults')) {
                $originalData = $validated;
                $validated = $model->applyDatabaseDefaults($validated, false);

                // Log defaults application for debugging
                $this->logDefaultsApplication($originalData, $validated, 'create', $modelName);
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
            );        } catch (\Illuminate\Database\QueryException $e) {
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
                'trace' => $e->getTraceAsString()
            ]);

            // Extract specific database error information
            $specificError = $this->parseDbError($e, $validated ?? []);

            return $this->errorResponse(
                'DATABASE_ERROR',
                'Database operation failed while creating the resource',
                500,
                array_merge($specificError, [
                    'model' => $modelName,
                    'error_code' => $errorCode,
                    'failed_data' => $validated ?? []
                ])
            );
        } catch (\Illuminate\Database\Eloquent\MassAssignmentException $e) {
            // Mass assignment protection error
            Log::error("Mass assignment error creating {$modelName} resource", [
                'error' => $e->getMessage(),
                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'MASS_ASSIGNMENT_ERROR',
                'One or more fields are not allowed to be mass assigned',
                500,
                [
                    'error_type' => 'mass_assignment_protection',
                    'attempted_data' => $validated ?? [],
                    'model' => $modelName,
                    'error_message' => $e->getMessage()
                ]
            );
        } catch (\TypeError $e) {
            // Type errors (wrong data types passed to model attributes)
            Log::error("Type error creating {$modelName} resource", [
                'error' => $e->getMessage(),                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString()
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
                    'line' => $e->getLine()
                ]
            );
        } catch (\Exception $e) {
            // Generic fallback for any other errors
            Log::error("Unexpected error creating {$modelName} resource", [
                'error_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString()
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
                    'line' => $e->getLine()
                ]
            );
        }
    }
    /**
     * Update the specified resource in storage.
     *
     * @param UpdateResourceRequest $request
     * @param string $id The resource ID
     * @return JsonResponse
     */
    public function update(UpdateResourceRequest $request, string $id): JsonResponse
    {
        $modelName = null;
        $validated = null;

        try {
            // Get the model class from the request
            $modelClass = $request->getModelClass();

            if (!$modelClass) {
                return response()->json([
                    'error' => 'Bad Request',
                    'message' => 'Model name not found in route defaults'
                ], 400);
            }

            // Validate that the class exists and is a Model
            if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
                $className = class_basename($modelClass);
                return response()->json([
                    'error' => 'Resource not found',
                    'message' => "Model '{$className}' not found or is not a valid Eloquent model"
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
                $this->logDefaultsApplication($originalData, $validated, 'update', $modelName);
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
            );        } catch (ValidationException $e) {
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
                'trace' => $e->getTraceAsString()
            ]);

            // Extract specific database error information
            $specificError = $this->parseDbError($e, $validated ?? []);

            return response()->json([
                'error' => 'Database error',
                'message' => 'Database operation failed while updating the resource',
                'details' => $specificError,
                'debug_info' => [
                    'model' => $modelName,
                    'resource_id' => $id,
                    'error_code' => $errorCode,
                    'failed_data' => $validated ?? []
                ]
            ], 500);
        } catch (\Illuminate\Database\Eloquent\MassAssignmentException $e) {
            // Mass assignment protection error
            Log::error("Mass assignment error updating {$modelName} resource with ID {$id}", [
                'error' => $e->getMessage(),
                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Mass assignment error',
                'message' => 'One or more fields are not allowed to be mass assigned',
                'details' => [
                    'error_type' => 'mass_assignment_protection',
                    'attempted_data' => $validated ?? []
                ],
                'debug_info' => [
                    'model' => $modelName,
                    'resource_id' => $id,
                    'error_message' => $e->getMessage()
                ]
            ], 500);
        } catch (\TypeError $e) {
            // Type errors (wrong data types passed to model attributes)
            Log::error("Type error updating {$modelName} resource with ID {$id}", [
                'error' => $e->getMessage(),
                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Data type error',
                'message' => 'Invalid data type provided for one or more fields',
                'details' => [
                    'error_type' => 'type_mismatch',
                    'error_message' => $e->getMessage(),
                    'attempted_data' => $validated ?? []
                ],
                'debug_info' => [
                    'model' => $modelName,
                    'resource_id' => $id,
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        } catch (\Exception $e) {
            // Generic fallback for any other errors
            Log::error("Unexpected error updating {$modelName} resource with ID {$id}", [
                'error_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'data' => $validated ?? $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred while updating the resource',
                'details' => [
                    'error_type' => 'unexpected_exception',
                    'error_class' => get_class($e),
                    'error_message' => $e->getMessage()
                ],
                'debug_info' => [
                    'model' => $modelName,
                    'resource_id' => $id,
                    'attempted_data' => $validated ?? [],
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param string $id The resource ID
     * @return JsonResponse
     */    public function destroy(Request $request, string $id): JsonResponse
    {
        $modelName = null;

        try {
            // Get model name from route defaults
            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? null;

            // Validate that we have a model name
            if (!$modelName) {
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
            if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
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
                'message' => 'Resource deleted successfully'
            ], 204);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                'RESOURCE_NOT_FOUND',
                "Resource with ID '{$id}' not found",
                404
            );
        } catch (\Exception $e) {
            Log::error("Error deleting " . ($modelName ?? 'unknown model') . " resource with ID {$id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
     *
     * @param Request $request
     * @return JsonResponse
     */    public function schema(Request $request): JsonResponse
    {
        $modelName = null;

        try {
            // Get model name from route defaults
            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? null;            // Validate that we have a model name
            if (!$modelName) {
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
            if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
                return $this->errorResponse(
                    'RESOURCE_NOT_FOUND',
                    "Model '{$className}' not found or is not a valid Eloquent model",
                    404
                );
            }

            $model = new $modelClass();

            // Check if model has custom schema method
            if (method_exists($model, 'getApiSchema')) {
                $customSchema = $model->getApiSchema();
                
                // If custom schema is grouped (array of arrays with 'group' and 'fields'), return it directly
                if (is_array($customSchema) && !empty($customSchema) && 
                    isset($customSchema[0]) && 
                    is_array($customSchema[0]) && 
                    isset($customSchema[0]['group']) && 
                    isset($customSchema[0]['fields'])) {
                    // This is a grouped schema, return it as-is
                    return $this->successResponse($customSchema);
                }
                
                // If custom schema is flat, wrap it in properties
                if (is_array($customSchema) && !empty($customSchema)) {
                    $schema = [
                        'properties' => $customSchema
                    ];
                    return $this->successResponse($schema);
                }
            }

            // Fallback: Auto-generate base schema from model introspection
            $autoSchema = $this->generateAutoSchema($model);
            
            $schema = [
                'properties' => $autoSchema
            ];

            return $this->successResponse($schema);
        } catch (\Exception $e) {
            Log::error("Error generating schema for " . ($modelName ?? 'unknown model'), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function columns(Request $request): JsonResponse
    {
        $modelName = null;

        try {
            // Get model name from route defaults
            $route = $request->route();
            $modelName = $route->defaults['modelName'] ?? null;            // Validate that we have a model name
            if (!$modelName) {
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
            if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
                return $this->errorResponse(
                    'RESOURCE_NOT_FOUND',
                    "Model '{$className}' not found or is not a valid Eloquent model",
                    404
                );
            }

            $model = new $modelClass();

            // Check if model has custom index columns method
            $customColumns = [];
            if (method_exists($model, 'getIndexColumns')) {
                $customColumns = $model->getIndexColumns();
            }            // If no custom columns defined, auto-generate from model
            if (empty($customColumns)) {
                $customColumns = $this->generateAutoIndexColumns($model);
            }

            return $this->successResponse($customColumns);
        } catch (\Exception $e) {
            Log::error("Error generating columns for " . ($modelName ?? 'unknown model'), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'INTERNAL_SERVER_ERROR',
                'An error occurred while generating the columns',
                500
            );
        }
    }

    /**
     * Auto-generate schema from model introspection.
     *
     * @param Model $model
     * @return array
     */
    protected function generateAutoSchema(Model $model): array
    {
        $fields = [];
        $fillable = $model->getFillable();
        $casts = $model->getCasts();
        $table = $model->getTable();

        foreach ($fillable as $field) {
            $fieldSchema = [
                'type' => $this->detectFieldType($field, $casts),
                'label' => $this->generateLabel($field),
                'placeholder' => $this->generatePlaceholder($field),
                'required' => false // Default to optional
            ];

            // Add type-specific properties
            $fieldSchema = array_merge($fieldSchema, $this->getTypeSpecificProperties($field, $casts, $table));

            $fields[$field] = $fieldSchema;
        }

        return $fields;
    }

    /**
     * Detect field type from field name and casts.
     *
     * @param string $field
     * @param array $casts
     * @return string
     */
    protected function detectFieldType(string $field, array $casts): string
    {
        // Check casts first for explicit type definitions
        if (isset($casts[$field])) {
            $cast = $casts[$field];

            return match ($cast) {
                'boolean', 'bool' => 'checkbox',
                'integer', 'int' => 'number',
                'float', 'double', 'real' => 'number',
                'decimal' => 'decimal',
                'datetime', 'timestamp' => 'datetime-local',
                'date' => 'date',
                'time' => 'time',
                'json', 'array', 'object' => 'textarea',
                default => 'text'
            };
        }

        // Field name pattern detection
        $field = strtolower($field);

        if (str_contains($field, 'email')) {
            return 'email';
        }

        if (str_contains($field, 'password')) {
            return 'password';
        }

        if (in_array($field, ['phone', 'mobile', 'whatsapp', 'tel'])) {
            return 'tel';
        }

        if (in_array($field, ['url', 'website', 'link'])) {
            return 'url';
        }

        if (in_array($field, ['color', 'colour'])) {
            return 'color';
        }

        if (str_contains($field, 'price') || str_contains($field, 'cost') || str_contains($field, 'amount')) {
            return 'decimal';
        }

        if (str_contains($field, 'percentage') || str_contains($field, 'percent') || str_contains($field, 'rate')) {
            return 'percentage';
        }

        if (str_contains($field, 'weight') || str_contains($field, 'height') || str_contains($field, 'width') || str_contains($field, 'length')) {
            return 'decimal';
        }

        if (str_contains($field, 'quantity') || str_contains($field, 'count') || str_contains($field, 'number')) {
            return 'number';
        }

        if (str_contains($field, 'description') || str_contains($field, 'content') || str_contains($field, 'notes')) {
            return 'textarea';
        }

        if (str_contains($field, 'image') || str_contains($field, 'photo') || str_contains($field, 'avatar')) {
            return 'file';
        }

        return 'text';
    }

    /**
     * Generate human-readable label from field name.
     *
     * @param string $field
     * @return string
     */
    protected function generateLabel(string $field): string
    {
        return Str::title(str_replace(['_', '-'], ' ', $field));
    }

    /**
     * Generate placeholder text for field.
     *
     * @param string $field
     * @return string
     */
    protected function generatePlaceholder(string $field): string
    {
        $label = $this->generateLabel($field);

        return match (true) {
            str_contains(strtolower($field), 'email') => 'Enter your email address',
            str_contains(strtolower($field), 'password') => 'Enter your password',
            str_contains(strtolower($field), 'phone') || str_contains(strtolower($field), 'mobile') => 'Enter your phone number',
            str_contains(strtolower($field), 'name') => "Enter your {$label}",
            str_contains(strtolower($field), 'description') => "Enter {$label}",
            default => "Enter {$label}"
        };
    }

    /**
     * Get type-specific properties for field.
     *
     * @param string $field
     * @param array $casts
     * @param string $table
     * @return array
     */
    protected function getTypeSpecificProperties(string $field, array $casts, string $table): array
    {
        $properties = [];
        $fieldLower = strtolower($field);

        // Common validation rules
        if (str_contains($fieldLower, 'email')) {
            $properties['unique'] = true;
            $properties['maxLength'] = 255;
        }

        if (str_contains($fieldLower, 'password')) {
            $properties['minLength'] = 8;
        }

        if (str_contains($fieldLower, 'phone') || str_contains($fieldLower, 'mobile') || str_contains($fieldLower, 'whatsapp')) {
            $properties['pattern'] = '^[0-9]{10,15}$';
            $properties['unique'] = true;
        }

        if (str_contains($fieldLower, 'username')) {
            $properties['unique'] = true;
            $properties['maxLength'] = 255;
        }

        // Type-specific properties
        if (isset($casts[$field])) {
            $cast = $casts[$field];

            if ($cast === 'decimal') {
                $properties['step'] = '0.01';
                $properties['min'] = '0';
            }

            if (in_array($cast, ['integer', 'int'])) {
                $properties['step'] = '1';
                $properties['min'] = '0';
            }

            if (in_array($cast, ['float', 'double', 'real'])) {
                $properties['step'] = '0.01';
            }
        }

        // Percentage fields
        if (str_contains($fieldLower, 'percentage') || str_contains($fieldLower, 'percent')) {
            $properties['min'] = '0';
            $properties['max'] = '100';
            $properties['step'] = '0.01';
            $properties['suffix'] = '%';
        }        // Price/cost fields
        if (str_contains($fieldLower, 'price') || str_contains($fieldLower, 'cost') || str_contains($fieldLower, 'amount')) {
            $properties['step'] = '0.01';
            $properties['min'] = '0';
            $properties['prefix'] = '₹';
        }

        // Weight/dimension fields
        if (str_contains($fieldLower, 'weight')) {
            $properties['step'] = '0.01';
            $properties['min'] = '0';
            $properties['suffix'] = 'kg';
        }

        if (in_array($fieldLower, ['height', 'width', 'length'])) {
            $properties['step'] = '0.01';
            $properties['min'] = '0';
            $properties['suffix'] = 'cm';
        }
        return $properties;
    }

    /**
     * Auto-generate index columns from model introspection.
     *
     * @param Model $model
     * @return array
     */
    protected function generateAutoIndexColumns(Model $model): array
    {
        $columns = [];
        $fillable = $model->getFillable();
        $casts = $model->getCasts();

        // Always include ID if it exists
        $columns['id'] = [
            'label' => 'ID',
            'sortable' => true
        ];

        // Add name field if it exists
        if (in_array('name', $fillable)) {
            $columns['name'] = [
                'label' => 'Name',
                'sortable' => true,
                'clickable' => true
            ];
        }

        // Add common fields with priority
        $commonFields = ['email', 'username', 'title', 'status', 'brand', 'sku', 'price'];

        foreach ($commonFields as $field) {
            if (in_array($field, $fillable)) {
                $columns[$field] = [
                    'label' => $this->generateLabel($field),
                    'sortable' => true,
                    'formatter' => $this->getColumnFormatter($field, $casts)
                ];
            }
        }

        // Add timestamps
        $columns['created_at'] = [
            'label' => 'Created',
            'sortable' => false,
            'formatter' => 'date'
        ];

        return $columns;
    }

    /**
     * Get appropriate formatter for column based on field name and cast type.
     *
     * @param string $field
     * @param array $casts
     * @return string|null
     */
    protected function getColumnFormatter(string $field, array $casts): ?string
    {
        $fieldLower = strtolower($field);

        // Check casts first
        if (isset($casts[$field])) {
            $cast = $casts[$field];
            return match ($cast) {
                'boolean', 'bool' => 'boolean',
                'decimal' => 'currency',
                'datetime', 'timestamp' => 'datetime',
                'date' => 'date',
                default => null
            };
        }

        // Field name patterns
        if (str_contains($fieldLower, 'price') || str_contains($fieldLower, 'cost') || str_contains($fieldLower, 'amount')) {
            return 'currency';
        }

        if (str_contains($fieldLower, 'status')) {
            return 'badge';
        }

        if (str_contains($fieldLower, 'quantity') || str_contains($fieldLower, 'count')) {
            return 'number';
        }
        return null;
    }

    /**
     * Get searchable fields from model's getIndexColumns method or fallback to text fields.
     *
     * @param Model $model
     * @return array
     */
    protected function getSearchableFields(Model $model): array
    {
        $searchableFields = [];

        // First, check if model has getIndexColumns method
        if (method_exists($model, 'getIndexColumns')) {
            $indexColumns = $model->getIndexColumns();

            // Filter columns where search is explicitly set to true
            foreach ($indexColumns as $field => $config) {
                if (isset($config['search']) && $config['search'] === true) {
                    $searchableFields[] = $field;
                }
            }
        }

        // If no searchable fields found from getIndexColumns, fallback to auto-detection
        if (empty($searchableFields)) {
            $searchableFields = $this->autoDetectSearchableFields($model);
        }

        return $searchableFields;
    }

    /**
     * Auto-detect searchable text fields from model's fillable array.
     *
     * @param Model $model
     * @return array
     */
    protected function autoDetectSearchableFields(Model $model): array
    {
        $fillable = $model->getFillable();
        $casts = $model->getCasts();
        $searchableFields = [];

        foreach ($fillable as $field) {
            $fieldLower = strtolower($field);

            // Skip non-text fields based on casts
            if (isset($casts[$field])) {
                $cast = $casts[$field];
                if (in_array($cast, ['boolean', 'bool', 'integer', 'int', 'float', 'double', 'real', 'decimal', 'datetime', 'timestamp', 'date', 'time'])) {
                    continue;
                }
            }

            // Skip password fields
            if (str_contains($fieldLower, 'password')) {
                continue;
            }

            // Include common text fields
            if (
                str_contains($fieldLower, 'name') ||
                str_contains($fieldLower, 'title') ||
                str_contains($fieldLower, 'description') ||
                str_contains($fieldLower, 'email') ||
                str_contains($fieldLower, 'username') ||
                str_contains($fieldLower, 'content') ||
                str_contains($fieldLower, 'notes') ||
                str_contains($fieldLower, 'brand') ||
                str_contains($fieldLower, 'sku') ||
                str_contains($fieldLower, 'model') ||
                str_contains($fieldLower, 'category')
            ) {
                $searchableFields[] = $field;
            }
        }
        return $searchableFields;
    }

    /**
     * Build response metadata for search and sorting information.
     *
     * @param Request $request
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return array
     */
    protected function buildResponseMetadata(Request $request, $query): array
    {
        $metadata = [];

        // Add search metadata
        if ($request->has('search') && !empty($request->input('search'))) {
            $metadata['search'] = [
                'query' => $request->input('search'),
                'fields' => $this->getSearchableFields(new ($query->getModel()::class))
            ];
        }

        // Add sorting metadata
        if ($request->has('sort')) {
            $sortFields = explode(',', $request->input('sort'));
            $directions = explode(',', $request->input('direction', 'asc'));

            $sortMetadata = [];
            foreach ($sortFields as $index => $sortField) {
                $sortField = trim($sortField);
                $direction = isset($directions[$index]) ? trim($directions[$index]) : 'asc';
                $sortMetadata[] = [
                    'field' => $sortField,
                    'direction' => $direction
                ];
            }

            $metadata['sort'] = $sortMetadata;
        }

        // Add total count (only for non-paginated requests with filters/search)
        if (!$request->has('page') && ($request->has('search') || $request->has('sort'))) {
            $metadata['total'] = $query->count();
        }
        return $metadata;
    }

    /**
     * Parse database error to provide more specific error information.
     *
     * @param \Illuminate\Database\QueryException $e
     * @param array $data
     * @return array
     */
    protected function parseDbError(\Illuminate\Database\QueryException $e, array $data): array
    {
        $errorMessage = $e->getMessage();
        $errorCode = $e->errorInfo[1] ?? null;

        // Common database error patterns
        if (str_contains($errorMessage, 'foreign key constraint')) {
            // Extract the constraint name if possible
            preg_match('/FOREIGN KEY constraint failed: (.+?)/', $errorMessage, $matches);
            $constraintInfo = $matches[1] ?? 'unknown constraint';

            return [
                'error_type' => 'foreign_key_constraint',
                'message' => 'Foreign key constraint violation',
                'constraint' => $constraintInfo,
                'suggestion' => 'Check that referenced IDs exist in related tables'
            ];
        }

        if (str_contains($errorMessage, 'NOT NULL constraint')) {
            // Extract field name from NOT NULL constraint error
            preg_match('/NOT NULL constraint failed: (\w+)\.(\w+)/', $errorMessage, $matches);
            $fieldName = $matches[2] ?? 'unknown field';

            return [
                'error_type' => 'required_field_missing',
                'message' => "Required field '{$fieldName}' cannot be null",
                'field' => $fieldName,
                'suggestion' => "Provide a value for the '{$fieldName}' field"
            ];
        }

        if (str_contains($errorMessage, 'UNIQUE constraint')) {
            // Extract field name from UNIQUE constraint error
            preg_match('/UNIQUE constraint failed: (\w+)\.(\w+)/', $errorMessage, $matches);
            $fieldName = $matches[2] ?? 'unknown field';
            $providedValue = $data[$fieldName] ?? 'unknown value';

            return [
                'error_type' => 'duplicate_value',
                'message' => "Duplicate value for field '{$fieldName}'",
                'field' => $fieldName,
                'provided_value' => $providedValue,
                'suggestion' => "The value '{$providedValue}' already exists. Use a unique value for '{$fieldName}'"
            ];
        }

        if (str_contains($errorMessage, 'Data too long')) {
            // Extract field information from data too long error
            preg_match('/Data too long for column \'(\w+)\'/', $errorMessage, $matches);
            $fieldName = $matches[1] ?? 'unknown field';
            $providedValue = $data[$fieldName] ?? null;

            return [
                'error_type' => 'data_too_long',
                'message' => "Data too long for field '{$fieldName}'",
                'field' => $fieldName,
                'provided_value' => $providedValue,
                'provided_length' => is_string($providedValue) ? strlen($providedValue) : null,
                'suggestion' => "Reduce the length of data for field '{$fieldName}'"
            ];
        }

        if (str_contains($errorMessage, 'Incorrect') && str_contains($errorMessage, 'value')) {
            // Data type mismatch errors
            preg_match('/Incorrect (\w+) value: \'([^\']*)\' for column \'(\w+)\'/', $errorMessage, $matches);
            $dataType = $matches[1] ?? 'unknown type';
            $providedValue = $matches[2] ?? ($data[$matches[3]] ?? 'unknown value');
            $fieldName = $matches[3] ?? 'unknown field';

            return [
                'error_type' => 'data_type_mismatch',
                'message' => "Invalid {$dataType} value for field '{$fieldName}'",
                'field' => $fieldName,
                'expected_type' => $dataType,
                'provided_value' => $providedValue,
                'suggestion' => "Provide a valid {$dataType} value for field '{$fieldName}'"
            ];
        }

        // Check for JSON field errors
        if (str_contains($errorMessage, 'Invalid JSON')) {
            // Find which field has invalid JSON
            foreach ($data as $field => $value) {
                if (is_string($value) && !json_decode($value) && json_last_error() !== JSON_ERROR_NONE) {
                    return [
                        'error_type' => 'invalid_json',
                        'message' => "Invalid JSON format for field '{$field}'",
                        'field' => $field,
                        'provided_value' => $value,
                        'json_error' => json_last_error_msg(),
                        'suggestion' => "Provide valid JSON format for field '{$field}'"
                    ];
                }
            }
        }

        // Generic database error fallback
        return [
            'error_type' => 'database_error',
            'message' => 'Database operation failed',
            'raw_error' => $errorMessage,
            'error_code' => $errorCode,
            'suggestion' => 'Check the data format and constraints'
        ];
    }

    /**
     * Log database defaults application for debugging.
     *
     * @param array $originalData
     * @param array $processedData
     * @param string $operation
     * @param string $modelName
     * @return void
     */
    protected function logDefaultsApplication(array $originalData, array $processedData, string $operation, string $modelName): void
    {
        $appliedDefaults = [];

        foreach ($processedData as $field => $value) {
            if (!array_key_exists($field, $originalData) || $originalData[$field] !== $value) {
                // Check if this was a null/empty value that got a default applied
                $originalValue = $originalData[$field] ?? 'NOT_PROVIDED';
                if ($originalValue === null || $originalValue === '' || $originalValue === 'NOT_PROVIDED') {
                    $appliedDefaults[$field] = [
                        'original' => $originalValue,
                        'applied_default' => $value
                    ];
                }
            }
        }        if (!empty($appliedDefaults)) {
            Log::info("Database defaults applied during {$operation} operation for {$modelName}", [
                'applied_defaults' => $appliedDefaults,
                'original_data' => $originalData,
                'processed_data' => $processedData
            ]);
        }
    }

    /**
     * Return a successful JSON response with standardized format.
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @param array $meta
     * @return JsonResponse
     */
    protected function successResponse($data = null, ?string $message = null, int $statusCode = 200, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response with standardized format.
     *
     * @param string $code
     * @param string $message
     * @param int $statusCode
     * @param array $details
     * @param array $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $code, string $message, int $statusCode = 400, array $details = [], array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];

        if (!empty($details)) {
            $response['error']['details'] = $details;
        }

        if (!empty($errors)) {
            $response['error']['validation_errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a paginated response with standardized format.
     *
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginatedResults
     * @param array $meta
     * @return JsonResponse
     */
    protected function paginatedResponse($paginatedResults, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $paginatedResults->items(),
            'pagination' => [
                'current_page' => $paginatedResults->currentPage(),
                'last_page' => $paginatedResults->lastPage(),
                'per_page' => $paginatedResults->perPage(),
                'total' => $paginatedResults->total(),
                'from' => $paginatedResults->firstItem(),
                'to' => $paginatedResults->lastItem(),
                'has_more_pages' => $paginatedResults->hasMorePages(),
                'path' => $paginatedResults->path(),
                'first_page_url' => $paginatedResults->url(1),
                'last_page_url' => $paginatedResults->url($paginatedResults->lastPage()),
                'next_page_url' => $paginatedResults->nextPageUrl(),
                'prev_page_url' => $paginatedResults->previousPageUrl(),
            ]
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response);
    }
}
