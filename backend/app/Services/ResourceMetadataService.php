<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for building response metadata for API resources.
 * Self-contained service that handles filters, search, sorting, schema, and columns metadata.
 *
 * Works in conjunction with:
 *
 * @see App\Services\ResourceSearchService
 * @see App\Services\ResourceFilterService
 * @see App\Services\ResourcePaginationService
 * @see App\Services\ResourceSortingService
 */
class ResourceMetadataService
{
    /**
     * Build comprehensive response metadata for API resources.
     *
     * @param  mixed  $query
     */
    public function buildResponseMetadata(Request $request, $query, array $appliedFilters = []): array
    {
        $metadata = [];

        // Get model instance for filter and search information
        $model = new ($query->getModel()::class);

        // Build filters object - always return (null if model has no getApiFilters method)
        $metadata['filters'] = $this->buildFiltersMetadata($request, $model, $appliedFilters);

        // Add search as string value or null (DESIGN.md format)
        $metadata['search'] = $this->buildSearchMetadata($request);

        // Add sorting metadata - always present at root level
        $metadata['sort'] = $this->buildSortMetadata($request);

        // Add schema data - always return (null if model has no getApiSchema method)
        $metadata['schema'] = $this->buildSchemaMetadata($model);

        // Add columns data - always return (never null, fallback to default ID column)
        $metadata['columns'] = $this->buildColumnsMetadata($model);

        return $metadata;
    }

    /**
     * Build filters metadata from request and model.
     */
    public function buildFiltersMetadata(Request $request, Model $model, array $appliedFilters = []): ?array
    {
        // Return null if model doesn't have getApiFilters method
        if (! method_exists($model, 'getApiFilters')) {
            return null;
        }

        // Get available filter fields directly inline
        $apiFilters = $model->getApiFilters();

        // Return null if no filters defined or empty
        if (empty($apiFilters)) {
            return null;
        }

        $availableFilterOptions = [];
        foreach ($apiFilters as $field => $config) {
            $availableFilterOptions[] = [
                'field' => $field,
                'value' => $config['values'] ?? [],
            ];
        }

        $filtersData = [
            'applied' => null,
            'available' => $availableFilterOptions,
        ];

        // Handle filter data from applyFilters
        if (! empty($appliedFilters)) {
            if (isset($appliedFilters['applied']) && ! empty($appliedFilters['applied'])) {
                // New format with enhanced metadata
                $activeFilters = [];
                foreach ($appliedFilters['applied'] as $field => $config) {
                    // Skip empty or invalid filter values
                    $filterValue = $config['value'] ?? null;
                    if ($filterValue !== null && $filterValue !== [] && $filterValue !== '') {
                        $activeFilters[] = [
                            'field' => $field,
                            'value' => $filterValue,
                        ];
                    }
                }

                // Set the first applied filter as the active one (since we use single filter policy)
                if (! empty($activeFilters)) {
                    $filtersData['applied'] = $activeFilters[0];
                }
            } elseif (! empty($appliedFilters)) {
                // Legacy format support - convert to new format
                $field = array_key_first($appliedFilters);
                $config = $appliedFilters[$field];
                $filterValue = $config['value'] ?? $config ?? null;

                // Only set applied filter if there's a valid value
                if ($filterValue !== null && $filterValue !== [] && $filterValue !== '') {
                    $filtersData['applied'] = [
                        'field' => $field,
                        'value' => $filterValue,
                    ];
                }
            }
        }

        return $filtersData;
    }

    /**
     * Build search metadata for API responses.
     */
    public function buildSearchMetadata(Request $request): ?string
    {
        if (! $request->has('search') || empty(trim($request->get('search')))) {
            return null;
        }

        $searchTerm = trim($request->get('search'));

        // Return null for invalid search terms (too short)
        // This ensures the search field is null when term is too short
        if (strlen($searchTerm) < 2) {
            return null;
        }

        return $searchTerm;
    }

    /**
     * Build sorting metadata from request.
     */
    public function buildSortMetadata(Request $request): ?array
    {
        if (! $request->has('sort')) {
            return null;
        }

        $sortColumn = trim($request->input('sort'));
        $direction = trim($request->input('dir', 'asc'));

        return [
            'column' => $sortColumn,
            'dir' => strtolower($direction),
        ];
    }

    /**
     * Build schema metadata for a model.
     * Returns null if model doesn't define getApiSchema() or returns null.
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
            Log::error('Error generating schema data for model', [
                'model' => get_class($model),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get default columns configuration for fallback.
     */
    public static function getDefaultColumns(): array
    {
        return [
            [
                'field' => 'id',
                'label' => 'ID',
                'sortable' => true,
                'clickable' => true,
                'search' => false,
                'format' => 'text',
                'align' => 'left',
            ],
        ];
    }

    /**
     * Build columns metadata for a model.
     * Never returns null - always provides fallback to default ID column.
     */
    public function buildColumnsMetadata(Model $model): array
    {
        try {
            // Check if model has custom index columns method
            if (method_exists($model, 'getIndexColumns')) {
                $customColumns = $model->getIndexColumns();

                // If custom columns are defined and not empty, return them directly
                if (! empty($customColumns)) {
                    return $customColumns;
                }
            }

            // Default to ID column array format if no custom columns defined
            return self::getDefaultColumns();
        } catch (\Exception $e) {
            Log::error('Error generating columns data for model', [
                'model' => get_class($model),
                'error' => $e->getMessage(),
            ]);

            // Even in error case, return default ID column (never null)
            return self::getDefaultColumns();
        }
    }
}
