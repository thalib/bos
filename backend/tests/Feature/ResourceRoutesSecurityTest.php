<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

// ==========================================
// Comprehensive Resource Routes Security Tests
// ==========================================

describe('Resource Routes Security', function () {
    test('all auto-generated resource routes require authentication', function () {
        // Test various resource endpoints that should be protected
        $resourceRoutes = [
            '/api/v1/users',
            '/api/v1/users/schema',
            '/api/v1/users/columns'
        ];

        foreach ($resourceRoutes as $route) {
            $response = $this->getJson($route);
            
            $response->assertStatus(401, "Route {$route} should require authentication");
        }
    });

    test('authenticated users can access resource routes', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $resourceRoutes = [
            '/api/v1/users',
            '/api/v1/users/schema', 
            '/api/v1/users/columns'
        ];

        foreach ($resourceRoutes as $route) {
            $response = $this->getJson($route);
            
            $response->assertStatus(200, "Authenticated user should access {$route}");
        }
    });

    test('CRUD operations on users require authentication', function () {
        $testUser = User::factory()->create();

        // Test CREATE (POST)
        $createResponse = $this->postJson('/api/v1/users', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $createResponse->assertStatus(401);

        // Test READ (GET)
        $readResponse = $this->getJson("/api/v1/users/{$testUser->id}");
        $readResponse->assertStatus(401);

        // Test UPDATE (PUT)
        $updateResponse = $this->putJson("/api/v1/users/{$testUser->id}", [
            'name' => 'Updated Name'
        ]);
        $updateResponse->assertStatus(401);

        // Test DELETE
        $deleteResponse = $this->deleteJson("/api/v1/users/{$testUser->id}");
        $deleteResponse->assertStatus(401);
    });

    test('authenticated users can perform CRUD operations on users', function () {
        $authenticatedUser = User::factory()->create();
        $targetUser = User::factory()->create();
        Sanctum::actingAs($authenticatedUser);

        // Test READ (GET)
        $readResponse = $this->getJson("/api/v1/users/{$targetUser->id}");
        $readResponse->assertStatus(200);

        // Test UPDATE (PUT)
        $updateResponse = $this->putJson("/api/v1/users/{$targetUser->id}", [
            'name' => 'Updated Name'
        ]);
        expect($updateResponse->status())->toBeIn([200, 422]); // 200 success or 422 validation error

        // Test CREATE (POST)
        $createResponse = $this->postJson('/api/v1/users', [
            'name' => 'New Test User',
            'username' => 'newtestuser',
            'email' => 'newtest@example.com',
            'password' => 'password123'
        ]);
        expect($createResponse->status())->toBeIn([200, 201, 422]); // Success or validation error

        // Test DELETE
        $userToDelete = User::factory()->create();
        $deleteResponse = $this->deleteJson("/api/v1/users/{$userToDelete->id}");
        expect($deleteResponse->status())->toBeIn([200, 204]); // Success
    });

    test('resource routes do not leak data without authentication', function () {
        // Create users with various sensitive data
        $users = User::factory(5)->create([
            'email_verified_at' => now(),
        ]);

        $sensitiveUser = User::factory()->create([
            'email' => 'admin@company.com',
            'name' => 'Admin User',
        ]);

        // Test that no data is returned for unauthenticated requests
        $routes = [
            '/api/v1/users',
            "/api/v1/users/{$sensitiveUser->id}",
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route);
            
            $response->assertStatus(401)
                ->assertJsonMissing(['email' => 'admin@company.com'])
                ->assertJsonMissing(['name' => 'Admin User'])
                ->assertJsonDoesntHaveKey('data');
        }
    });

    test('invalid tokens are properly rejected on resource routes', function () {
        $invalidTokens = [
            'invalid-token',
            'Bearer invalid-token',
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.invalid.signature',
            ''
        ];

        foreach ($invalidTokens as $token) {
            $headers = [];
            if ($token) {
                $headers['Authorization'] = $token;
            }

            $response = $this->withHeaders($headers)->getJson('/api/v1/users');
            
            $response->assertStatus(401);
        }
    });

    test('expired tokens are rejected on resource routes', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Revoke all tokens to simulate expiration
        $user->tokens()->delete();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/users');

        $response->assertStatus(401);
    });

    test('resource routes maintain security across different HTTP methods', function () {
        $user = User::factory()->create();
        
        $httpMethods = [
            'get' => ['/api/v1/users', '/api/v1/users/' . $user->id],
            'post' => ['/api/v1/users'],
            'put' => ['/api/v1/users/' . $user->id],
            'patch' => ['/api/v1/users/' . $user->id],
            'delete' => ['/api/v1/users/' . $user->id]
        ];

        foreach ($httpMethods as $method => $routes) {
            foreach ($routes as $route) {
                $response = $this->{$method . 'Json'}($route, [
                    'name' => 'Test Data',
                    'email' => 'test@example.com'
                ]);
                
                $response->assertStatus(401, "Method {$method} on route {$route} should require authentication");
            }
        }
    });

    test('bulk operations require authentication', function () {
        // Test potential bulk endpoints if they exist
        $bulkEndpoints = [
            ['method' => 'post', 'route' => '/api/v1/users/bulk'],
            ['method' => 'put', 'route' => '/api/v1/users/bulk'],
            ['method' => 'delete', 'route' => '/api/v1/users/bulk'],
        ];

        foreach ($bulkEndpoints as $endpoint) {
            $response = $this->{$endpoint['method'] . 'Json'}($endpoint['route'], [
                'ids' => [1, 2, 3]
            ]);
            
            // Should be 401 (unauthorized) or 404 (not found) - but not 200
            expect($response->status())->toBeIn([401, 404]);
        }
    });
});

// ==========================================
// Resource Routes Data Validation Tests
// ==========================================

describe('Resource Routes Data Protection', function () {
    test('authenticated users receive proper data structure from resource routes', function () {
        $user = User::factory()->create();
        User::factory(3)->create(); // Create additional users
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        // Add other expected fields based on your User model
                    ]
                ]
            ]);
    });

    test('sensitive user fields are handled appropriately', function () {
        $user = User::factory()->create();
        $targetUser = User::factory()->create([
            'password' => bcrypt('secret-password'),
            'remember_token' => 'secret-token'
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(200);
        
        // Ensure sensitive fields are not exposed
        $responseData = $response->json();
        expect($responseData)->not->toHaveKey('password');
        expect($responseData)->not->toHaveKey('remember_token');
    });

    test('schema endpoint provides proper field definitions for authenticated users', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/users/schema');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'properties' => [
                    '*' => [
                        'type'
                    ]
                ]
            ]);
    });

    test('columns endpoint provides proper column information for authenticated users', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/users/columns');

        $response->assertStatus(200)
            ->assertJson([]);
    });
});

// ==========================================
// Mixed Authentication State Tests
// ==========================================

describe('Mixed Authentication Scenarios', function () {
    test('switching from authenticated to unauthenticated properly restricts access', function () {
        $user = User::factory()->create();
        
        // First, authenticate and access resource
        Sanctum::actingAs($user);
        $authResponse = $this->getJson('/api/v1/users');
        $authResponse->assertStatus(200);

        // Then, make request without authentication
        $this->app['auth']->forgetGuards(); // Clear authentication
        $unauthResponse = $this->getJson('/api/v1/users');
        $unauthResponse->assertStatus(401);
    });

    test('token invalidation immediately prevents access to resource routes', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Test with valid token
        $validResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/users');
        $validResponse->assertStatus(200);

        // Invalidate token
        $user->tokens()->delete();

        // Test with invalidated token
        $invalidResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/users');
        $invalidResponse->assertStatus(401);
    });
});
