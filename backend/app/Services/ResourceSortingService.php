<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ResourceSortingService
{
    /**
     * Process sorting parameters and apply to query
     */
    public function processSorting(Request $request, Model $model): array
    {
        $validation = $this->validateSortingInput($request, $model);

        $sortingParams = $this->processSortingParameters($request, $model);

        // Start with notifications from validation
        $notifications = $validation['notifications'];

        return [
            'success' => true,
            'notifications' => $notifications,
            'sortBy' => $sortingParams['sortBy'],
            'sortOrder' => $sortingParams['sortOrder'],
        ];
    }

    /**
     * Validate sorting input parameters
     */
    public function validateSortingInput(Request $request, Model $model): array
    {
        $sortBy = $request->get('sort');
        $sortOrder = $request->get('dir', 'asc');
        $notifications = [];

        if ($sortBy) {
            $sortableColumns = $this->getSortableColumns($model);

            if (! in_array($sortBy, $sortableColumns)) {
                $notifications[] = [
                    'type' => 'warning',
                    'message' => "Sort column '{$sortBy}' not found, using default 'id'",
                ];
            }
        }

        if (! in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $notifications[] = [
                'type' => 'warning',
                'message' => "Sort direction '{$sortOrder}' not recognized, using 'asc'",
            ];
        }

        return ['success' => true, 'notifications' => $notifications];
    }

    /**
     * Get sortable columns from model
     */
    protected function getSortableColumns(Model $model): array
    {
        if (method_exists($model, 'getIndexColumns')) {
            $columns = $model->getIndexColumns();
            $sortableColumns = [];

            foreach ($columns as $column) {
                if (isset($column['sortable']) && $column['sortable'] === true) {
                    $sortableColumns[] = $column['field'];
                }
            }

            // Always include created_at as it's a common default
            if (! in_array('created_at', $sortableColumns)) {
                $sortableColumns[] = 'created_at';
            }

            return $sortableColumns;
        }

        if (method_exists($model, 'getSortableColumns')) {
            return $model->getSortableColumns();
        }

        return ['id', 'created_at', 'updated_at'];
    }

    /**
     * Process sorting parameters
     */
    protected function processSortingParameters(Request $request, Model $model): array
    {
        $sortBy = $request->get('sort');
        $sortOrder = strtolower($request->get('dir', 'asc'));

        $sortableColumns = $this->getSortableColumns($model);

        // If no sort parameter provided, use defaults
        if (! $sortBy) {
            return [
                'sortBy' => 'id',
                'sortOrder' => 'asc',
            ];
        }

        // Validate sort column
        if (! in_array($sortBy, $sortableColumns)) {
            $sortBy = 'id';
        }

        // Validate sort direction
        if (! in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }

        return [
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ];
    }
}
