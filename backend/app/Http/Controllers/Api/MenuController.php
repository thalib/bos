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
                        'id' => 61,
                        'name' => 'Estimate',
                        'path' => '/list/estimates',
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

        return response()->json([
            'data' => $menuItems,
            'message' => 'Menu items retrieved successfully',
        ]);
    }
}
