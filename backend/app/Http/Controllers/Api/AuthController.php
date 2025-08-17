<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RefreshRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Responses\ApiResponseTrait;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Handle user login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $requestId = $this->getOrGenerateRequestId($request);

        // Log login attempt
        Log::info('login.attempt', [
            'request_id' => $requestId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $identifier = $request->validated()['username'];
        $password = $request->validated()['password'];

        // Resolve user by identifier (email, username, or whatsapp)
        $user = $this->resolveUserByIdentifier($identifier);

        if (! $user || ! Hash::check($password, $user->password)) {
            Log::info('login.failure', [
                'request_id' => $requestId,
                'identifier' => $identifier,
                'ip' => $request->ip(),
                'reason' => 'invalid_credentials',
            ]);

            return $this->errorResponse(
                'auth.invalid_credentials',
                'The provided credentials are incorrect.',
                401
            );
        }

        // Check if user is active
        if (! $user->active) {
            Log::info('login.failure', [
                'request_id' => $requestId,
                'actor_id' => $user->id,
                'ip' => $request->ip(),
                'reason' => 'inactive_account',
            ]);

            return $this->errorResponse(
                'auth.account_inactive',
                'Your account is not active. Please contact an administrator.',
                403
            );
        }

        // Create tokens
        $accessTokenName = config('auth.token_names.auth', 'auth_token');
        $refreshTokenName = config('auth.token_names.refresh', 'refresh_token');

        $accessToken = $user->createToken($accessTokenName)->plainTextToken;
        $refreshToken = $user->createToken($refreshTokenName, ['refresh'])->plainTextToken;

        Log::info('login.success', [
            'request_id' => $requestId,
            'actor_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        $responseData = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => null,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'whatsapp' => $user->whatsapp,
                'role' => $user->role->value,
                'active' => $user->active,
            ],
        ];

        return $this->simpleSuccessResponse($responseData, 'Logged in')
            ->header('X-Request-Id', $requestId);
    }

    /**
     * Register a new user (admin-only)
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $requestId = $this->getOrGenerateRequestId($request);
        $adminUser = $request->user();

        // Double-check authorization (defense in depth)
        if ($adminUser?->role !== UserRole::ADMIN) {
            Log::info('admin.register.unauthorized', [
                'request_id' => $requestId,
                'actor_id' => $adminUser?->id,
                'actor_role' => $adminUser?->role?->value,
            ]);

            return $this->errorResponse(
                'auth.insufficient_permissions',
                'Only administrators can register new users.',
                403
            )->header('X-Request-Id', $requestId);
        }

        Log::info('admin.register.attempt', [
            'request_id' => $requestId,
            'actor_id' => $adminUser?->id,
            'target_email' => $request->validated()['email'],
            'target_username' => $request->validated()['username'],
        ]);

        $validatedData = $request->validated();

        // Normalize whatsapp to E.164 format
        $validatedData['whatsapp'] = $this->normalizeWhatsappNumber($validatedData['whatsapp']);

        // Set defaults
        $validatedData['role'] = $validatedData['role'] ?? UserRole::USER->value;
        $validatedData['active'] = true;
        $validatedData['password'] = Hash::make($validatedData['password']);

        try {
            $user = User::create($validatedData);

            // Create tokens
            $accessTokenName = config('auth.token_names.auth', 'auth_token');
            $refreshTokenName = config('auth.token_names.refresh', 'refresh_token');

            $accessToken = $user->createToken($accessTokenName)->plainTextToken;
            $refreshToken = $user->createToken($refreshTokenName, ['refresh'])->plainTextToken;

            Log::info('admin.register.success', [
                'request_id' => $requestId,
                'actor_id' => $adminUser?->id,
                'created_user_id' => $user->id,
                'created_user_email' => $user->email,
            ]);

            $responseData = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'whatsapp' => $user->whatsapp,
                    'role' => $user->role->value,
                    'active' => $user->active,
                ],
                'accessToken' => $accessToken,
                'refreshToken' => $refreshToken,
            ];

            return $this->simpleSuccessResponse($responseData, 'User created', 201)
                ->header('X-Request-Id', $requestId);

        } catch (\Exception $e) {
            Log::error('admin.register.failure', [
                'request_id' => $requestId,
                'actor_id' => $adminUser?->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'user.creation_failed',
                'Failed to create user. Please try again.',
                500
            )->header('X-Request-Id', $requestId);
        }
    }

    /**
     * Refresh access token using refresh token
     */
    public function refresh(RefreshRequest $request): JsonResponse
    {
        $requestId = $this->getOrGenerateRequestId($request);
        $refreshTokenInput = $request->validated()['refreshToken'];

        // Check if using deprecated id|token format
        $refreshToken = $refreshTokenInput;
        if (str_contains($refreshTokenInput, '|')) {
            $parts = explode('|', $refreshTokenInput);
            if (count($parts) === 2) {
                $refreshToken = $parts[1];
                Log::warning('refresh.deprecated_format', [
                    'request_id' => $requestId,
                    'message' => 'Deprecated id|token format used for refresh token',
                ]);
            }
        }

        // Find the token in the database
        $tokenModel = PersonalAccessToken::findToken($refreshToken);

        if (! $tokenModel || ! $tokenModel->tokenable) {
            Log::info('refresh.failure', [
                'request_id' => $requestId,
                'reason' => 'invalid_token',
            ]);

            return $this->errorResponse(
                'auth.invalid_refresh_token',
                'Invalid refresh token',
                401
            )->header('X-Request-Id', $requestId);
        }

        // Check if token has refresh ability
        if (! in_array('refresh', $tokenModel->abilities)) {
            Log::info('refresh.failure', [
                'request_id' => $requestId,
                'token_id' => $tokenModel->id,
                'reason' => 'missing_refresh_ability',
            ]);

            return $this->errorResponse(
                'auth.invalid_refresh_token',
                'Invalid refresh token',
                401
            )->header('X-Request-Id', $requestId);
        }

        // Get the user from the token
        $user = $tokenModel->tokenable;

        // Revoke the old refresh token (rotation)
        $tokenModel->delete();

        // Create new tokens
        $accessTokenName = config('auth.token_names.auth', 'auth_token');
        $refreshTokenName = config('auth.token_names.refresh', 'refresh_token');

        $newAccessToken = $user->createToken($accessTokenName)->plainTextToken;
        $newRefreshToken = $user->createToken($refreshTokenName, ['refresh'])->plainTextToken;

        Log::info('refresh.success', [
            'request_id' => $requestId,
            'actor_id' => $user->id,
        ]);

        $responseData = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'whatsapp' => $user->whatsapp,
                'role' => $user->role->value,
                'active' => $user->active,
            ],
            'accessToken' => $newAccessToken,
            'refreshToken' => $newRefreshToken,
        ];

        return $this->simpleSuccessResponse($responseData, 'Token refreshed')
            ->header('X-Request-Id', $requestId);
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request): JsonResponse
    {
        $requestId = $this->getOrGenerateRequestId($request);
        $user = Auth::user();

        if ($user) {
            // Revoke all tokens for the authenticated user
            $user->tokens()->delete();

            Log::info('logout.success', [
                'request_id' => $requestId,
                'actor_id' => $user->id,
                'ip' => $request->ip(),
            ]);
        }

        return $this->simpleSuccessResponse(null, 'Logged out')
            ->header('X-Request-Id', $requestId);
    }

    /**
     * Check authentication status
     */
    public function status(Request $request): JsonResponse
    {
        $requestId = $this->getOrGenerateRequestId($request);
        $user = Auth::user();
        $isAuthenticated = Auth::check();

        $responseData = [
            'authenticated' => $isAuthenticated,
            'user' => $isAuthenticated && $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'whatsapp' => $user->whatsapp,
                'role' => $user->role->value,
                'active' => $user->active,
            ] : null,
        ];

        return $this->simpleSuccessResponse($responseData, 'Status')
            ->header('X-Request-Id', $requestId);
    }

    /**
     * Resolve user by identifier (email, username, or whatsapp)
     */
    private function resolveUserByIdentifier(string $identifier): ?User
    {
        // Try email first
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $identifier)->first();
        }

        // Try whatsapp (normalize first, then check if it looks like a phone number)
        $normalizedWhatsapp = $this->normalizeWhatsappNumber($identifier);
        if (preg_match('/^[+][0-9]{8,15}$/', $normalizedWhatsapp)) {
            return User::where('whatsapp', $normalizedWhatsapp)->first();
        }

        // Try username as fallback
        return User::where('username', $identifier)->first();
    }

    /**
     * Normalize WhatsApp number to E.164 format
     */
    private function normalizeWhatsappNumber(string $whatsapp): string
    {
        // Remove any non-numeric characters except +
        $cleaned = preg_replace('/[^+0-9]/', '', $whatsapp);

        // Ensure it starts with +
        if (! str_starts_with($cleaned, '+')) {
            $cleaned = '+'.$cleaned;
        }

        return $cleaned;
    }

    /**
     * Get or generate request ID for correlation
     */
    private function getOrGenerateRequestId(Request $request): string
    {
        $requestId = $request->header('X-Request-Id') ?? Str::uuid()->toString();
        $request->headers->set('X-Request-Id', $requestId);

        return $requestId;
    }
}
