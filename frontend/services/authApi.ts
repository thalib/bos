/**
 * Auth API Service for Thanzil project
 * Provides authentication-specific API methods
 */
import { useApiService } from './api'
import type { ApiResponse } from '~/types'
import type { LoginCredentials, LoginResponse } from './auth'

/**
 * Auth API Service
 * Handles authentication API requests
 */
export const useAuthApi = () => {
  const api = useApiService()
  
  /**
   * Login API endpoint
   * @param credentials User login credentials
   * @returns API response with login data
   */
  const login = (credentials: LoginCredentials): Promise<ApiResponse<LoginResponse>> => {
    return api.request<LoginResponse>('auth/login', {
      method: 'POST',
      body: credentials,
      skipInterceptors: true // Skip auth interceptors for login
    })
  }
  
  /**
   * Refresh token API endpoint
   * @param refreshToken The refresh token
   * @returns API response with refreshed tokens
   */
  const refreshToken = (refreshToken: string): Promise<ApiResponse<LoginResponse>> => {
    return api.request<LoginResponse>('auth/refresh', {
      method: 'POST',
      body: { refreshToken },
      skipInterceptors: true // Skip auth interceptors for token refresh
    })
  }
  
  /**
   * Logout API endpoint
   * @returns API response after logout
   */
  const logout = (): Promise<ApiResponse<void>> => {
    return api.request<void>('auth/logout', {
      method: 'POST'
    })
  }
  
  /**
   * Check authentication status
   * @returns API response with current auth status
   */
  const checkAuthStatus = (): Promise<ApiResponse<{ authenticated: boolean }>> => {
    return api.request<{ authenticated: boolean }>('auth/status', {
      method: 'GET'
    })
  }
  
  /**
   * Register a new user
   * @param userData New user data
   * @returns API response with registration result
   */
  const register = (userData: any): Promise<ApiResponse<LoginResponse>> => {
    return api.request<LoginResponse>('auth/register', {
      method: 'POST',
      body: userData,
      skipInterceptors: true // Skip auth interceptors for registration
    })
  }
  
  /**
   * Request password reset
   * @param email User email
   * @returns API response after request
   */
  const requestPasswordReset = (email: string): Promise<ApiResponse<{ message: string }>> => {
    return api.request<{ message: string }>('auth/password/reset-request', {
      method: 'POST',
      body: { email }
    })
  }
  
  /**
   * Reset password with token
   * @param resetData Password reset data
   * @returns API response after password reset
   */
  const resetPassword = (resetData: { token: string; email: string; password: string; password_confirmation: string }): Promise<ApiResponse<{ message: string }>> => {
    return api.request<{ message: string }>('auth/password/reset', {
      method: 'POST',
      body: resetData
    })
  }
  
  return {
    login,
    logout,
    refreshToken,
    checkAuthStatus,
    register,
    requestPasswordReset,
    resetPassword
  }
}
