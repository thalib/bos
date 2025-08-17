<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * MenuController handles the application menu retrieval.
 *
 * This controller is an explicit exception during development:
 * menu-building logic is kept in-controller rather than extracted
 * to a service while the menu structure is actively changing.
 */
class MenuController extends Controller
{
    /**
     * Get menu items for authenticated user.
     *
     * Returns the application menu using the project standard envelope.
     * Optionally caches the menu based on configuration flags.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if caching is enabled
            if (config('app.menu_cache_enabled', false)) {
                $cacheKey = "menu:v1:user:{$user->id}";
                $cacheTtl = config('app.menu_ttl', 300);

                $menu = Cache::remember($cacheKey, $cacheTtl, function () use ($user) {
                    return $this->buildMenuForUser($user);
                });
            } else {
                $menu = $this->buildMenuForUser($user);
            }

            return response()->json([
                'success' => true,
                'message' => 'Menu items retrieved successfully',
                'data' => $menu,
            ], 200);

        } catch (\Exception $e) {
            // Log the error with context for debugging
            Log::error('menu.fetch_failed', [
                'user_id' => $request->user()?->id,
                'route' => $request->route()?->getName() ?: 'app.menu',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve menu at this time.',
            ], 500);
        }
    }

    /**
     * Build the menu for the given user.
     *
     * This helper method contains the actual menu generation logic.
     * Kept in-controller during development while menu structure is changing.
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    private function buildMenuForUser(User $user): array
    {
        // Menu items with unique IDs and better structure
        return [
            [
                'type' => 'item',
                'id' => 1,
                'name' => 'Home',
                'path' => '/',
                'icon' => 'bi-house',
            ],
            [
                'type' => 'section',
                'title' => 'List',
                'items' => [
                    [
                        'id' => 20,
                        'name' => 'Products',
                        'path' => '/list/products',
                        'icon' => 'bi-calculator',
                    ],
                ],
            ],
            [
                'type' => 'divider',
            ],
            [
                'type' => 'section',
                'title' => 'Sales',
                'items' => [
                    [
                        'id' => 40,
                        'name' => 'Estimate',
                        'path' => '/list/estimates',
                        'icon' => 'bi-receipt',
                    ],
                ],
            ],
            [
                'type' => 'divider',
            ],
            [
                'type' => 'section',
                'title' => 'Administration',
                'items' => [
                    [
                        'id' => 60,
                        'name' => 'Users',
                        'path' => '/list/users',
                        'icon' => 'bi-people',
                        'mode' => 'form',
                    ],
                    [
                        'id' => 62, // Fixed: Changed from 61 to 62 to ensure unique IDs
                        'name' => 'Admin Estimates', // Fixed: Changed name to differentiate from Sales Estimate
                        'path' => '/admin/estimates',
                        'icon' => 'bi-receipt',
                        'mode' => 'doc',
                    ],
                ],
            ],
            [
                'type' => 'divider',
            ],
            [
                'type' => 'item',
                'id' => 90,
                'name' => 'Help',
                'path' => '/help',
                'icon' => 'bi-question-circle',
            ],
        ];
    }
}
