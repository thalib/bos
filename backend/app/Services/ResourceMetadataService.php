<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for building response metadata for API resources.
 * Self-contained service that handles filters, search, sorting, schema, and columns metadata.
 */
class ResourceMetadataService
{

    /**
     * Build comprehensive response metadata for API resources.
     *
     * @param Request $request
     * @param mixed $query
     * @param array $appliedFilters
     * @return array
     */
    public function buildResponseMetadata(Request $request, $query, array $appliedFilters = []): array
    {
        $metadata = [];

        // Get model instance for filter and search information
        $model = new ($query->getModel()::class);
        
        // Build filters object
        $metadata['filters'] = $this->buildFiltersMetadata($request, $model, $appliedFilters);

        // Add search as string value or null (DESIGN.md format)
        $metadata['search'] = $this->buildSearchMetadata($request);

        // Add sorting metadata - always present at root level
        $metadata['sort'] = $this->buildSortMetadata($request);

        // Add schema data (same as schema endpoint)
        $schemaData = $this->buildSchemaMetadata($model);
        if ($schemaData !== null) {
            $metadata['schema'] = $schemaData;
        }

        // Add columns data (same as columns endpoint)
        $columnsData = $this->buildColumnsMetadata($model);
        if ($columnsData !== null) {
            $metadata['columns'] = $columnsData;
        }

        return $metadata;
    }

    /**
     * Build filters metadata from request and model.
     *
     * @param Request $request
     * @param Model $model
     * @param array $appliedFilters
     * @return array
     */
    public function buildFiltersMetadata(Request $request, Model $model, array $appliedFilters = []): array
    {
        $availableFilterOptions = $this->getAvailableFilterFields($model);
        $filtersData = [
            'applied' => null,
            'availableOptions' => $availableFilterOptions
        ];

        // Handle filter data from applyFilters
        if (!empty($appliedFilters)) {
            if (isset($appliedFilters['applied']) && !empty($appliedFilters['applied'])) {
                // New format with enhanced metadata
                $activeFilters = [];
                foreach ($appliedFilters['applied'] as $field => $config) {
                    // Skip empty or invalid filter values
                    $filterValue = $config['value'] ?? null;
                    if ($filterValue !== null && $filterValue !== [] && $filterValue !== '') {
                        $activeFilters[] = [
                            'field' => $field,
                            'value' => $filterValue
                        ];
                    }
                }
                
                // Set the first applied filter as the active one (since we use single filter policy)
                if (!empty($activeFilters)) {
                    $filtersData['applied'] = $activeFilters[0];
                }
            } elseif (!empty($appliedFilters)) {
                // Legacy format support - convert to new format
                $field = array_key_first($appliedFilters);
                $config = $appliedFilters[$field];
                $filterValue = $config['value'] ?? $config ?? null;
                
                // Only set applied filter if there's a valid value
                if ($filterValue !== null && $filterValue !== [] && $filterValue !== '') {
                    $filtersData['applied'] = [
                        'field' => $field,
                        'value' => $filterValue
                    ];
                }
            }
        }

        return $filtersData;
    }

    /**
     * Get available filter fields from model.
     *
     * @param Model $model
     * @return array|null
     */
    protected function getAvailableFilterFields(Model $model): ?array
    {
        // Only check if model has custom API filters
        if (method_exists($model, 'getApiFilters')) {
            $apiFilters = $model->getApiFilters();
            
            // Return null if no filters defined or empty
            if (empty($apiFilters)) {
                return null;
            }
            
            $availableFilters = [];
            foreach ($apiFilters as $field => $config) {
                $availableFilters[] = [
                    'field' => $field,
                    'value' => $config['values'] ?? []
                ];
            }
            
            return $availableFilters;
        }
        
        // Return null if getApiFilters method is not defined
        return null;
    }

    /**
     * Build search metadata for API responses.
     *
     * @param Request $request
     * @return string|null
     */
    public function buildSearchMetadata(Request $request): ?string
    {
        return ($request->has('search') && !empty($request->input('search'))) 
            ? $request->input('search') 
            : null;
    }

    /**
     * Build sorting metadata from request.
     *
     * @param Request $request
     * @return array|null
     */
    public function buildSortMetadata(Request $request): ?array
    {
        if (!$request->has('sort')) {
            return null;
        }

        $sortFields = explode(',', $request->input('sort'));
        $directions = explode(',', $request->input('sort_dir', 'asc'));

        $sortArray = [];
        foreach ($sortFields as $index => $sortField) {
            $sortField = trim($sortField);
            $direction = isset($directions[$index]) ? trim($directions[$index]) : 'asc';
            $sortArray[] = [
                'field' => $sortField,
                'direction' => $direction
            ];
        }

        return $sortArray;
    }

    /**
     * Build schema metadata for a model.
     * Returns null if model doesn't define getApiSchema() or returns null.
     *
     * @param Model $model
     * @return array|null
     */
    public function buildSchemaMetadata(Model $model): ?array
    {
        try {
            // Check if model has custom schema method
            if (method_exists($model, 'getApiSchema')) {
                $schema = $model->getApiSchema();
                return $schema ?: null;
            }

            // Return null if getApiSchema method is not defined
            return null;
        } catch (\Exception $e) {
            Log::error("Error generating schema data for model", [
                'model' => get_class($model),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Build columns metadata for a model.
     * Returns default ID column if model doesn't define getIndexColumns() or returns null.
     *
     * @param Model $model
     * @return array|null
     */
    public function buildColumnsMetadata(Model $model): ?array
    {
        try {
            // Check if model has custom index columns method
            if (method_exists($model, 'getIndexColumns')) {
                $customColumns = $model->getIndexColumns();
                
                // If custom columns are defined and not empty, return them
                if (!empty($customColumns)) {
                    return $customColumns;
                }
            }

            // Default to ID column if no custom columns defined
            return [
                'id' => [
                    'label' => 'ID',
                    'sortable' => true,
                    'clickable' => true,
                    'search' => true
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Error generating columns data for model", [
                'model' => get_class($model),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
