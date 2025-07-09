<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Trait for standardized API response formatting.
 * Provides consistent response structures across all API controllers.
 */
trait ApiResponseTrait
{
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

        // Return data array directly at top level (not nested)
        if ($data !== null) {
            $response['data'] = $data;
        }

        // Include pagination object at top level for paginated responses
        if (!empty($meta['pagination'])) {
            $response['pagination'] = $meta['pagination'];
        }

        // Include search at top level - string value or null (DESIGN.md format)
        $response['search'] = $meta['search'] ?? null;

        // Include filters object at top level for filtered responses
        if (!empty($meta['filters'])) {
            $response['filters'] = $meta['filters'];
        }

        // Include schema object at top level
        if (!empty($meta['schema'])) {
            $response['schema'] = $meta['schema'];
        }

        // Include columns object at top level
        if (!empty($meta['columns'])) {
            $response['columns'] = $meta['columns'];
        }

        // Include any remaining metadata (sort, total)
        if (!empty($meta)) {
            $filteredMeta = array_diff_key($meta, [
                'pagination' => true, 
                'filters' => true, 
                'schema' => true, 
                'columns' => true,
                'search' => true
            ]);
            if (!empty($filteredMeta)) {
                $response['meta'] = $filteredMeta;
            }
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
     * @param LengthAwarePaginator $paginatedResults
     * @param array $meta
     * @return JsonResponse
     */
    protected function paginatedResponse(LengthAwarePaginator $paginatedResults, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $paginatedResults->items(),
            'pagination' => [
                'totalItems' => $paginatedResults->total(),
                'currentPage' => $paginatedResults->currentPage(),
                'itemsPerPage' => $paginatedResults->perPage(),
                'totalPages' => $paginatedResults->lastPage(),
                'urlPath' => $this->buildPaginationUrlPath($paginatedResults),
                'urlQuery' => $this->buildPaginationUrlQuery($paginatedResults),
                'nextPage' => $paginatedResults->nextPageUrl(),
                'prevPage' => $paginatedResults->previousPageUrl(),
            ]
        ];

        // Include search at top level - string value or null (DESIGN.md format)
        $response['search'] = $meta['search'] ?? null;

        // Include sort at top level - array or null (always present)
        $response['sort'] = $meta['sort'] ?? null;

        // Include filters object at top level for filtered responses
        if (!empty($meta['filters'])) {
            $response['filters'] = $meta['filters'];
        }

        // Include schema object at top level
        if (!empty($meta['schema'])) {
            $response['schema'] = $meta['schema'];
        }

        // Include columns object at top level
        if (!empty($meta['columns'])) {
            $response['columns'] = $meta['columns'];
        }

        // Include any remaining metadata (excluding moved fields)
        if (!empty($meta)) {
            $filteredMeta = array_diff_key($meta, [
                'filters' => true, 
                'schema' => true, 
                'columns' => true,
                'search' => true,
                'sort' => true
            ]);
            if (!empty($filteredMeta)) {
                $response['meta'] = $filteredMeta;
            }
        }

        return response()->json($response);
    }

    /**
     * Build pagination URL path for generating page URLs.
     *
     * @param LengthAwarePaginator $paginatedResults
     * @return string
     */
    protected function buildPaginationUrlPath(LengthAwarePaginator $paginatedResults): string
    {
        // Get the current URL with all query parameters
        $currentUrl = request()->fullUrl();
        
        // Parse URL components
        $parsedUrl = parse_url($currentUrl);
        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        
        if (isset($parsedUrl['port'])) {
            $baseUrl .= ':' . $parsedUrl['port'];
        }
        
        $baseUrl .= $parsedUrl['path'];
        
        return $baseUrl;
    }

    /**
     * Build pagination URL query string for generating page URLs.
     *
     * @param LengthAwarePaginator $paginatedResults
     * @return string|null
     */
    protected function buildPaginationUrlQuery(LengthAwarePaginator $paginatedResults): ?string
    {
        // Get the current URL with all query parameters
        $currentUrl = request()->fullUrl();
        
        // Parse URL components
        $parsedUrl = parse_url($currentUrl);
        
        // Parse query parameters and remove 'page' parameter
        $queryParams = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
            unset($queryParams['page']);
        }
        
        // Return query string without page parameter (frontend will add it)
        if (!empty($queryParams)) {
            return http_build_query($queryParams);
        } else {
            return null;
        }
    }
}
