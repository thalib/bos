<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Get menu items for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        // Menu items with better structure
        $menuItems = [
            [
                'type' => 'item',
                'id' => 1,
                'name' => 'Home',
                'path' => '/',
                'icon' => 'bi-house',
                'order' => 1,
            ],
            [
                'type' => 'section',
                'title' => 'List',
                'order' => 2,
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
                'order' => 3,
            ],
            [
                'type' => 'section',
                'title' => 'Sales',
                'order' => 4,
                'items' => [
                    [
                        'id' => 40,
                        'name' => 'Estimate',
                        'path' => '/doc/estimates',
                        'icon' => 'bi-receipt',
                    ],
                ],
            ],
            [
                'type' => 'divider',
                'order' => 5,
            ],
            [
                'type' => 'section',
                'title' => 'Administration',
                'order' => 6,
                'items' => [
                    [
                        'id' => 60,
                        'name' => 'Users',
                        'path' => '/list/users',
                        'icon' => 'bi-people',
                    ],
                ],
            ],
            [
                'type' => 'divider',
                'order' => 7,
            ],
            [
                'type' => 'item',
                'id' => 90,
                'name' => 'Help',
                'path' => '/help',
                'icon' => 'bi-question-circle',
                'order' => 8,
            ],
        ];

        return response()->json([
            'data' => $menuItems,
            'message' => 'Menu items retrieved successfully',
        ]);
    }
}
