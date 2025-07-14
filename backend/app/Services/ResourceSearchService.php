<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ResourceSearchService
{
    /**
     * Apply search filters to query and return result with notifications
     */
    public function applySearchFilters(Builder $query, Request $request, Model $model): array
    {
        $notifications = [];

        if (! $request->has('search') || empty(trim($request->get('search')))) {
            return [
                'success' => true,
                'notifications' => $notifications,
                'query' => $query,
            ];
        }

        $searchTerm = trim($request->get('search'));

        // Check if search term is too short - graceful fallback with notification
        if (strlen($searchTerm) < 2) {
            $notifications[] = [
                'type' => 'warning',
                'message' => 'Search term too short (minimum 2 characters), search ignored',
            ];

            return [
                'success' => true,
                'notifications' => $notifications,
                'query' => $query,
            ];
        }

        $searchableFields = $this->getSearchableFields($model);
        if (empty($searchableFields)) {
            $notifications[] = [
                'type' => 'warning',
                'message' => 'Search is not supported for this resource.',
            ];

            return [
                'success' => true,
                'notifications' => $notifications,
                'query' => $query,
            ];
        }

        $this->applySearch($query, $searchTerm, $searchableFields);

        return [
            'success' => true,
            'notifications' => $notifications,
            'query' => $query,
        ];
    }

    /**
     * Validate search input parameters
     */
    public function validateSearchInput(Request $request): array
    {
        $searchTerm = $request->get('search');

        if (! $this->validateSearchParameter($searchTerm)) {
            return [
                'success' => false,
                'notifications' => [[
                    'type' => 'error',
                    'message' => 'Search term must be at least 2 characters long.',
                ]],
            ];
        }

        return ['success' => true, 'notifications' => []];
    }

    /**
     * Apply search conditions to query
     */
    protected function applySearch(Builder $query, string $searchTerm, array $searchableFields): void
    {
        $query->where(function ($q) use ($searchTerm, $searchableFields) {
            foreach ($searchableFields as $field) {
                $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
            }
        });
    }

    /**
     * Validate search parameter
     */
    protected function validateSearchParameter(string $searchTerm): bool
    {
        return strlen(trim($searchTerm)) >= 2;
    }

    /**
     * Get searchable fields from model
     */
    protected function getSearchableFields(Model $model): array
    {
        if (method_exists($model, 'getSearchableFields')) {
            return $model->getSearchableFields();
        }

        return [];
    }
}
