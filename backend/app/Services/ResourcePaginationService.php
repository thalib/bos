<?php

namespace App\Services;

use Illuminate\Http\Request;

class ResourcePaginationService
{
    /**
     * Process pagination parameters and return result with notifications
     */
    public function processPagination(Request $request, int $totalItems): array
    {
        $validation = $this->validatePaginationInput($request);
        if (! $validation['success']) {
            return $validation;
        }

        $paginationParams = $this->processPaginationParameters($request);

        // Start with notifications from validation (including warnings)
        $notifications = $validation['notifications'];

        // Check if per_page was adjusted due to max limit
        $requestedPerPage = $request->get('per_page', 15);
        if (is_numeric($requestedPerPage) && (int) $requestedPerPage > 100) {
            $notifications[] = [
                'type' => 'warning',
                'message' => 'Per page value exceeds maximum limit of 100.',
            ];
        }

        // Validate page against total items
        $pageValidation = $this->validatePageAgainstTotal(
            $paginationParams['page'],
            $paginationParams['perPage'],
            $totalItems
        );

        if (! $pageValidation['success']) {
            return $pageValidation;
        }

        // Merge any notifications from page validation
        $notifications = array_merge($notifications, $pageValidation['notifications']);

        return [
            'success' => true,
            'notifications' => $notifications,
            'page' => $paginationParams['page'],
            'perPage' => $paginationParams['perPage'],
        ];
    }

    /**
     * Validate pagination input parameters
     */
    public function validatePaginationInput(Request $request): array
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 15);
        $notifications = [];

        // Graceful fallback for invalid page values
        if ($request->has('page') && (! is_numeric($page) || (int) $page < 1)) {
            $notifications[] = [
                'type' => 'warning',
                'message' => 'Invalid page number, using page 1',
            ];
        }

        // Graceful fallback for invalid per_page values
        if ($request->has('per_page') && ! is_numeric($perPage)) {
            $notifications[] = [
                'type' => 'warning',
                'message' => 'Page size must be a positive integer. Using default value of 15.',
            ];
        }

        // Handle per_page over maximum limit - fallback to maximum
        if (is_numeric($perPage) && (int) $perPage > 100) {
            $notifications[] = [
                'type' => 'warning',
                'message' => 'Page size exceeds maximum of 100, using maximum 100.',
            ];
        }

        // Handle per_page under minimum limit - fallback to minimum
        if (is_numeric($perPage) && (int) $perPage < 1) {
            $notifications[] = [
                'type' => 'warning',
                'message' => 'Page size below minimum of 1, using minimum 1.',
            ];
        }

        return ['success' => true, 'notifications' => $notifications];
    }

    /**
     * Process pagination parameters
     */
    protected function processPaginationParameters(Request $request): array
    {
        $page = (int) $request->get('page', 1);
        $perPage = $request->get('per_page', 15);

        // Handle invalid page values
        if (! is_numeric($request->get('page', 1)) || $page < 1) {
            $page = 1;
        }

        // Handle invalid per_page values
        if (! is_numeric($perPage)) {
            $perPage = 15; // Fall back to default for non-numeric values
        } else {
            $perPage = (int) $perPage;
            // Handle out-of-range per_page values with appropriate fallbacks
            if ($perPage > 100) {
                $perPage = 100; // Fall back to maximum for over-limit values
            } elseif ($perPage < 1) {
                $perPage = 1;   // Fall back to minimum for under-limit values
            }
        }

        return [
            'page' => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Validate page number against total items
     */
    protected function validatePageAgainstTotal(int $page, int $perPage, int $totalItems): array
    {
        if ($totalItems === 0) {
            return ['success' => true, 'notifications' => []];
        }

        $maxPage = (int) ceil($totalItems / $perPage);

        if ($page > $maxPage) {
            return [
                'success' => true,
                'notifications' => [[
                    'type' => 'warning',
                    'message' => "Requested page {$page} exceeds available pages. Showing page {$maxPage}.",
                ]],
            ];
        }

        return ['success' => true, 'notifications' => []];
    }
}
