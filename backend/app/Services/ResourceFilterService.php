<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Service responsible for handling filtering and searching operations on API resources.
 * Provides methods for applying filters, detecting searchable fields, and managing search queries.
 */
class ResourceFilterService
{
    /**
     * Apply search filtering to a query based on request parameters.
     */
    public function applySearch(Builder $query, Request $request, Model $model): void
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
     */
    public function applyFilters(Builder $query, Request $request, Model $model): array
    {
        $appliedFilters = [];

        // Check if model has custom API filters
        if (method_exists($model, 'getApiFilters')) {
            $apiFilters = $model->getApiFilters();

            foreach ($apiFilters as $field => $config) {
                $filterParam = $request->input($field);
                if ($filterParam && in_array($filterParam, $config['values'])) {
                    $appliedFilters[$field] = [
                        'value' => $filterParam,
                        'label' => $config['label'] ?? ucfirst($field),
                    ];

                    // Apply the filter to the query using scope if available
                    $this->applyFilterToQuery($query, $field, $filterParam, $model);
                }
            }
        } else {
            // Fallback: Legacy active filter if model has 'active' field
            $fillableFields = $model->getFillable();
            if (in_array('active', $fillableFields)) {
                $filterParam = $request->input('filter');
                if ($filterParam) {
                    switch ($filterParam) {
                        case 'active':
                            $this->applyActiveScope($query, $model, true);
                            $appliedFilters['active'] = ['value' => 'Active'];
                            break;
                        case 'inactive':
                            $this->applyActiveScope($query, $model, false);
                            $appliedFilters['active'] = ['value' => 'Inactive'];
                            break;
                        default:
                            // 'all' or any other value - no filter applied
                            break;
                    }
                }
            }
        }

        return $appliedFilters;
    }

    /**
     * Get searchable fields from model's getIndexColumns method or fallback to text fields.
     */
    public function getSearchableFields(Model $model): array
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
     */
    public function autoDetectSearchableFields(Model $model): array
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
     * Get available filter fields for a model.
     */
    public function getAvailableFilterFields(Model $model): ?array
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
                    'value' => $config['values'] ?? [],
                ];
            }

            return $availableFilters;
        }

        // Return null if getApiFilters method is not defined
        return null;
    }

    /**
     * Apply a specific filter to the query using Eloquent scopes when available.
     *
     * @param  mixed  $value
     */
    protected function applyFilterToQuery(Builder $query, string $field, $value, Model $model): void
    {
        // Check if model has a scope for this filter
        $scopeMethod = 'scope'.ucfirst(Str::camel($field));

        if (method_exists($model, $scopeMethod)) {
            // Use the model's scope if available
            $query->{Str::camel($field)}($value);
        } else {
            // Apply specific field logic or fallback to generic where clause
            switch ($field) {
                case 'status':
                    $query->where('status', $value);
                    break;
                case 'channel':
                    $query->where('channel', $value);
                    break;
                case 'active':
                    $this->applyActiveScope($query, $model, $value === 'active');
                    break;
                default:
                    // Generic where clause for other fields
                    $query->where($field, $value);
                    break;
            }
        }
    }

    /**
     * Apply active/inactive filtering using scope if available or direct where clause.
     */
    protected function applyActiveScope(Builder $query, Model $model, bool $active): void
    {
        // Check if model has an active scope
        if (method_exists($model, 'scopeActive')) {
            if ($active) {
                $query->active();
            } else {
                $query->where('active', false);
            }
        } elseif (method_exists($model, 'scopeInactive')) {
            if ($active) {
                $query->where('active', true);
            } else {
                $query->inactive();
            }
        } else {
            // Fallback to direct where clause
            $query->where('active', $active);
        }
    }

    /**
     * Build comprehensive filter metadata for API responses.
     */
    public function buildFilterMetadata(Request $request, Model $model, array $appliedFilters = []): array
    {
        $availableFilterOptions = $this->getAvailableFilterFields($model);
        $filtersData = [
            'applied' => null,
            'availableOptions' => $availableFilterOptions,
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
     * Get search metadata for API responses.
     */
    public function getSearchMetadata(Request $request): ?string
    {
        return ($request->has('search') && ! empty($request->input('search')))
            ? $request->input('search')
            : null;
    }
}
