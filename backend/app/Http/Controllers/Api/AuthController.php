<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Handle user login
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // First try login with email as username
        $field = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        // Check if user is active
        if (! $user->active) {
            return response()->json([
                'message' => 'Your account is not active. Please contact an administrator.',
            ], 403);
        }

        // Create access token
        $accessToken = $user->createToken('auth_token')->plainTextToken;

        // Create refresh token (store in database with expiry)
        $refreshToken = $user->createToken('refresh_token', ['refresh'])->plainTextToken;

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => null, // Sanctum tokens don't expire by default
            'user' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'whatsapp' => $user->whatsapp,
            ],
            'message' => 'Successfully logged in',
        ]);
    }

    /**
     * Refresh access token using refresh token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refreshToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Extract the actual token from "refresh_token|ACTUAL_TOKEN"
        $refreshToken = explode('|', $request->refreshToken)[1] ?? null;

        if (! $refreshToken) {
            return response()->json([
                'message' => 'Invalid refresh token format',
            ], 401);
        }

        // Find the token in the database
        $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($refreshToken);

        if (! $tokenModel || ! $tokenModel->tokenable) {
            return response()->json([
                'message' => 'Invalid refresh token',
            ], 401);
        }

        // Get the user from the token
        $user = $tokenModel->tokenable;

        // Revoke the refresh token
        $tokenModel->delete();

        // Create new tokens
        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $newRefreshToken = $user->createToken('refresh_token', ['refresh'])->plainTextToken;

        return response()->json([
            'user' => $user,
            'accessToken' => $accessToken,
            'refreshToken' => $newRefreshToken,
            'message' => 'Token refreshed successfully',
        ]);
    }

    /**
     * Handle user logout
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoke all tokens for the authenticated user
        if (Auth::check()) {
            Auth::user()->tokens()->delete();
        }

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Check authentication status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(Request $request)
    {
        return response()->json([
            'authenticated' => Auth::check(),
            'user' => Auth::check() ? Auth::user() : null,
        ]);
    }

    /**
     * Register a new user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'whatsapp' => 'required|string|regex:/^[0-9]{10,15}$/|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'whatsapp' => $request->whatsapp,
            'password' => Hash::make($request->password),
        ]);

        // Create tokens
        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', ['refresh'])->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
        ], 201);
    }
}
