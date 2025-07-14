<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Trait for standardized API response formatting.
 * Provides consistent response structures across all API controllers.
 */
trait ApiResponseTrait
{
    /**
     * Return a successful JSON response with standardized format.
     *
     * @param  mixed  $data
     */
    protected function successResponse($data = null, ?string $message = null, int $statusCode = 200, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        // Return data array directly at top level (not nested)
        if ($data !== null) {
            $response['data'] = $data;
        }

        // Include pagination object at top level for paginated responses
        if (! empty($meta['pagination'])) {
            $response['pagination'] = $meta['pagination'];
        }

        // Include search at top level - string value or null
        $response['search'] = $meta['search'] ?? null;

        // Always include filters field (null if no filters available)
        $response['filters'] = $meta['filters'] ?? null;

        // Always include schema field (null if not available)
        $response['schema'] = $meta['schema'] ?? null;

        // Always include columns field (never null, fallback to ID column)
        if (isset($meta['columns'])) {
            $response['columns'] = $meta['columns'];
        } elseif ($data !== null && is_array($data) && ! empty($data)) {
            // Only include default columns for list responses
            $response['columns'] = $this->getDefaultColumns();
        }

        // Always include notifications field (null if no notifications)
        $response['notifications'] = $meta['notifications'] ?? null;

        // Include any remaining metadata (sort, total)
        if (! empty($meta)) {
            $filteredMeta = array_diff_key($meta, [
                'pagination' => true,
                'filters' => true,
                'schema' => true,
                'columns' => true,
                'search' => true,
                'notifications' => true,
            ]);
            if (! empty($filteredMeta)) {
                $response['meta'] = $filteredMeta;
            }
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response with standardized format.
     */
    protected function errorResponse(string $code, string $message, int $statusCode = 400, array $details = [], array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'error' => [
                'code' => $code,
                'details' => $details,
            ],
        ];

        if (! empty($errors)) {
            $response['error']['validation_errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a paginated response with standardized format.
     */
    protected function paginatedResponse(LengthAwarePaginator $paginatedResults, array $meta = [], ?string $message = null): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        // Add optional message field at root level
        if ($message) {
            $response['message'] = $message;
        }

        $response['data'] = $paginatedResults->items();

        // Complete pagination structure with urlPath and urlQuery
        $queryParams = request()->except(['page', 'per_page']);
        $pagination = [
            'totalItems' => $paginatedResults->total(),
            'currentPage' => $paginatedResults->currentPage(),
            'itemsPerPage' => $paginatedResults->perPage(),
            'totalPages' => $paginatedResults->lastPage(),
            'urlPath' => $paginatedResults->path(),
            'urlQuery' => ! empty($queryParams) ? $queryParams : null,
            'nextPage' => $paginatedResults->hasMorePages() ? (string) ($paginatedResults->currentPage() + 1) : null,
            'prevPage' => $paginatedResults->currentPage() > 1 ? (string) ($paginatedResults->currentPage() - 1) : null,
        ];

        $response['pagination'] = $pagination;

        // Include search at top level - string value or null
        $response['search'] = $meta['search'] ?? null;

        // Include sort at top level - array or null
        $response['sort'] = $meta['sort'] ?? null;

        // Always include filters field (null if no filters available)
        $response['filters'] = $meta['filters'] ?? null;

        // Always include schema field (null if not available)
        $response['schema'] = $meta['schema'] ?? null;

        // Always include columns field (never null, fallback to ID column)
        $response['columns'] = $meta['columns'] ?? $this->getDefaultColumns();

        // Always include notifications field (null if no notifications)
        $response['notifications'] = $meta['notifications'] ?? null;

        // Include any remaining metadata (excluding moved fields)
        if (! empty($meta)) {
            $filteredMeta = array_diff_key($meta, [
                'filters' => true,
                'schema' => true,
                'columns' => true,
                'search' => true,
                'sort' => true,
                'notifications' => true,
            ]);
            if (! empty($filteredMeta)) {
                $response['meta'] = $filteredMeta;
            }
        }

        return response()->json($response);
    }

    /**
     * Get default columns configuration for fallback.
     */
    protected function getDefaultColumns(): array
    {
        return \App\Services\ResourceMetadataService::getDefaultColumns();
    }
}
