<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ResourceFilterService
{
    /**
     * Apply resource filters to query and return result with notifications
     */
    public function applyResourceFilters(Builder $query, Request $request, Model $model): array
    {
        $notifications = [];

        // Handle generic filter parameter (field:value format) regardless of getApiFilters
        if ($request->has('filter')) {
            $filterParam = $request->get('filter');
            $filterResult = $this->handleGenericFilter($query, $filterParam, $model);

            if (isset($filterResult['notifications'])) {
                $notifications = array_merge($notifications, $filterResult['notifications']);
            }
        }

        // Early return if no getApiFilters method or no filters available
        if (! method_exists($model, 'getApiFilters')) {
            return [
                'success' => true,
                'notifications' => $notifications,
                'query' => $query,
            ];
        }

        $availableFilters = $model->getApiFilters();
        if (empty($availableFilters)) {
            return [
                'success' => true,
                'notifications' => $notifications,
                'query' => $query,
            ];
        }

        // Handle specific filter parameters
        foreach ($availableFilters as $filterKey => $filterConfig) {
            $filterParam = $filterConfig['parameter'] ?? $filterKey;

            if ($request->has($filterParam)) {
                $filterValue = $request->get($filterParam);

                $validation = $this->validateFilterInput($filterParam, $model);
                if (! $validation['success']) {
                    return $validation;
                }

                $this->applyFilterToQuery($query, $filterKey, $filterValue, $filterConfig);
            }
        }

        return [
            'success' => true,
            'notifications' => $notifications,
            'query' => $query,
        ];
    }

    /**
     * Validate filter input parameter
     */
    public function validateFilterInput(string $filterParam, Model $model): array
    {
        if (! method_exists($model, 'getApiFilters')) {
            return [
                'success' => false,
                'notifications' => [[
                    'type' => 'error',
                    'message' => 'Filtering is not supported for this resource.',
                ]],
            ];
        }

        $availableFilters = $model->getApiFilters();
        $filterExists = false;

        foreach ($availableFilters as $filterKey => $filterConfig) {
            $parameter = $filterConfig['parameter'] ?? $filterKey;
            if ($parameter === $filterParam) {
                $filterExists = true;
                break;
            }
        }

        if (! $filterExists) {
            return [
                'success' => false,
                'notifications' => [[
                    'type' => 'error',
                    'message' => "Invalid filter parameter: {$filterParam}",
                ]],
            ];
        }

        return ['success' => true, 'notifications' => []];
    }

    /**
     * Apply individual filter to query
     */
    protected function applyFilterToQuery(Builder $query, string $filterKey, $filterValue, array $filterConfig): void
    {
        $column = $filterConfig['column'] ?? $filterKey;
        $operator = $filterConfig['operator'] ?? '=';

        if (isset($filterConfig['values']) && is_array($filterConfig['values'])) {
            if (! in_array($filterValue, $filterConfig['values'])) {
                return;
            }
        }

        if ($filterValue === 'all') {
            return;
        }

        if (isset($filterConfig['callback']) && is_callable($filterConfig['callback'])) {
            $filterConfig['callback']($query, $filterValue);
        } else {
            $query->where($column, $operator, $filterValue);
        }
    }

    /**
     * Handle generic filter parameter (field:value format)
     */
    protected function handleGenericFilter(Builder $query, string $filterParam, Model $model): array
    {
        $notifications = [];

        // Validate filter format (should be field:value)
        if (! str_contains($filterParam, ':')) {
            $notifications[] = [
                'type' => 'warning',
                'message' => 'Filter format invalid_format not recognized, filter ignored',
            ];

            return ['notifications' => $notifications];
        }

        // Parse field and value
        $parts = explode(':', $filterParam, 2);
        $field = trim($parts[0]);
        $value = trim($parts[1]);

        // Check if model has getApiFilters method
        if (! method_exists($model, 'getApiFilters')) {
            $notifications[] = [
                'type' => 'warning',
                'message' => 'Filtering is not supported for this resource',
                'field' => 'filter',
            ];

            return ['notifications' => $notifications];
        }

        // Validate that the field exists in available filters
        $availableFilters = $model->getApiFilters();
        if (empty($availableFilters)) {
            $notifications[] = [
                'type' => 'warning',
                'message' => 'No filters are available for this resource',
                'field' => 'filter',
            ];

            return ['notifications' => $notifications];
        }

        $fieldExists = false;
        $filterConfig = null;

        foreach ($availableFilters as $filterKey => $config) {
            if ($filterKey === $field) {
                $fieldExists = true;
                $filterConfig = $config;
                break;
            }
        }

        if (! $fieldExists) {
            $notifications[] = [
                'type' => 'warning',
                'message' => "Invalid filter field: {$field}",
                'field' => 'filter',
            ];

            return ['notifications' => $notifications];
        }

        // Apply the filter if field is valid
        if ($filterConfig) {
            $this->applyFilterToQuery($query, $field, $value, $filterConfig);
        }

        return ['notifications' => $notifications];
    }
}
