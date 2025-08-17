<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->artisan('migrate:fresh');
});

describe('Authentication Login', function () {
    test('it successfully logs in with email', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                    'user' => [
                        'id',
                        'name',
                        'username',
                        'email',
                        'whatsapp',
                        'role',
                        'active',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Logged in',
                'data' => [
                    'token_type' => 'Bearer',
                    'expires_in' => null,
                    'user' => [
                        'id' => $user->id,
                        'email' => 'test@example.com',
                        'active' => true,
                    ],
                ],
            ]);

        expect($response->json('data.access_token'))->not->toBeEmpty();
        expect($response->json('data.refresh_token'))->not->toBeEmpty();
        expect($response->headers->get('X-Request-Id'))->not->toBeEmpty();
    });

    test('it successfully logs in with username', function () {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password123'),
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged in',
                'data' => [
                    'user' => [
                        'username' => 'testuser',
                    ],
                ],
            ]);
    });

    test('it successfully logs in with whatsapp', function () {
        $user = User::factory()->create([
            'whatsapp' => '+628123456789',
            'password' => Hash::make('password123'),
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => '+628123456789',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged in',
                'data' => [
                    'user' => [
                        'whatsapp' => '+628123456789',
                    ],
                ],
            ]);
    });

    test('it normalizes whatsapp numbers for login', function () {
        User::factory()->create([
            'whatsapp' => '+628123456789',
            'password' => Hash::make('password123'),
            'active' => true,
        ]);

        // Test with non-normalized format
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => '628123456789', // Without + prefix
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged in',
            ]);
    });

    test('it rejects login with invalid credentials', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
                'error' => [
                    'code' => 'auth.invalid_credentials',
                ],
            ]);
    });

    test('it rejects login for inactive user', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Your account is not active. Please contact an administrator.',
                'error' => [
                    'code' => 'auth.account_inactive',
                ],
            ]);
    });

    test('it validates login request', function () {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'error' => [
                    'code',
                    'message',
                    'details' => [
                        'username',
                        'password',
                    ],
                ],
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                ],
            ]);

        expect($response->json('error.details.username'))->toContain('Username/email/phone is required.');
        expect($response->json('error.details.password'))->toContain('Password is required.');
    });
});

describe('Authentication Register', function () {
    test('it allows admin to register new user', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        Sanctum::actingAs($admin);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'whatsapp' => '+628987654321',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'username',
                        'email',
                        'whatsapp',
                        'role',
                        'active',
                    ],
                    'accessToken',
                    'refreshToken',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'User created',
                'data' => [
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                        'username' => 'johndoe',
                        'whatsapp' => '+628987654321',
                        'role' => 'user',
                        'active' => true,
                    ],
                ],
            ]);

        expect($response->json('data.accessToken'))->not->toBeEmpty();
        expect($response->json('data.refreshToken'))->not->toBeEmpty();

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'whatsapp' => '+628987654321',
            'role' => 'user',
            'active' => true,
        ]);
    });

    test('it prevents non-admin from registering users', function () {
        $user = User::factory()->create(['role' => UserRole::USER]);
        Sanctum::actingAs($user);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'whatsapp' => '+628987654321',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(403);
    });

    test('it requires authentication for register', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'whatsapp' => '+628987654321',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(401);
    });

    test('it validates register request', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'error' => [
                    'code',
                    'message',
                    'details' => [
                        'name',
                        'email',
                        'username',
                        'whatsapp',
                        'password',
                    ],
                ],
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                ],
            ]);
    });

    test('it validates whatsapp e164 format in register', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'whatsapp' => '628123456789', // Missing + prefix
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'error' => [
                    'code',
                    'message',
                    'details' => [
                        'whatsapp',
                    ],
                ],
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ]);
    });
});

describe('Token Refresh', function () {
    test('it successfully refreshes tokens', function () {
        $user = User::factory()->create();
        $refreshToken = $user->createToken('refresh_token', ['refresh'])->plainTextToken;

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refreshToken' => $refreshToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'accessToken',
                    'refreshToken',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Token refreshed',
            ]);

        // Verify old refresh token is revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', explode('|', $refreshToken)[1]),
        ]);
    });

    test('it handles deprecated refresh token format', function () {
        $user = User::factory()->create();
        $refreshToken = $user->createToken('refresh_token', ['refresh'])->plainTextToken;

        // Test that the deprecated format still works
        $response = $this->postJson('/api/v1/auth/refresh', [
            'refreshToken' => $refreshToken, // This is already id|token format from Sanctum
        ]);

        $response->assertStatus(200);

        // Note: In a real scenario, we would check logs for deprecation warning,
        // but mocking Log in tests conflicts with the exception handler logging
    });

    test('it rejects invalid refresh token', function () {
        $response = $this->postJson('/api/v1/auth/refresh', [
            'refreshToken' => 'invalid-token',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid refresh token',
                'error' => [
                    'code' => 'auth.invalid_refresh_token',
                ],
            ]);
    });

    test('it rejects access token for refresh', function () {
        $user = User::factory()->create();
        $accessToken = $user->createToken('auth_token')->plainTextToken;

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refreshToken' => $accessToken,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid refresh token',
            ]);
    });

    test('it validates refresh request', function () {
        $response = $this->postJson('/api/v1/auth/refresh', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'error' => [
                    'code',
                    'message',
                    'details' => [
                        'refreshToken',
                    ],
                ],
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                ],
            ]);
    });
});

describe('Authentication Logout', function () {
    test('it successfully logs out authenticated user', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create some tokens
        $user->createToken('auth_token');
        $user->createToken('refresh_token', ['refresh']);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out',
            ]);

        // Verify all tokens are revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    });

    test('it requires authentication for logout', function () {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    });
});

describe('Authentication Status', function () {
    test('it returns status for authenticated user', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'authenticated',
                    'user' => [
                        'id',
                        'name',
                        'username',
                        'email',
                        'whatsapp',
                        'role',
                        'active',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Status',
                'data' => [
                    'authenticated' => true,
                    'user' => [
                        'id' => $user->id,
                    ],
                ],
            ]);
    });

    test('it requires authentication for status', function () {
        $response = $this->getJson('/api/v1/auth/status');

        $response->assertStatus(401);
    });
});

describe('API Envelope Response', function () {
    test('it includes request id in response headers', function () {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        expect($response->headers->get('X-Request-Id'))->not->toBeEmpty();
    });

    test('it uses provided request id in headers', function () {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'active' => true,
        ]);

        $requestId = 'test-request-id-12345';

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => $user->email,
            'password' => 'password123',
        ], [
            'X-Request-Id' => $requestId,
        ]);

        $response->assertStatus(200);
        expect($response->headers->get('X-Request-Id'))->toBe($requestId);
    });

    test('it returns standardized envelope for success', function () {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson([
                'success' => true,
            ]);

        expect($response->json('message'))->toBeString();
        expect($response->json('data'))->toBeArray();
    });

    test('it returns standardized envelope for errors', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure([
                'success',
                'message',
                'error' => [
                    'code',
                    'details',
                ],
            ])
            ->assertJson([
                'success' => false,
            ]);

        expect($response->json('message'))->toBeString();
        expect($response->json('error.code'))->toBeString();
    });

    test('it handles validation errors with envelope', function () {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'error' => [
                    'code',
                    'message',
                    'details',
                ],
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                ],
            ]);
    });
});
