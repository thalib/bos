## Summary

* **Endpoint:** /api/v1/auth/*
* **Method:** POST (login, register, refresh, logout), GET (status)
* **Authentication:** Mixed — `login` is public; `register` is admin-only; `refresh`/`logout`/`status` require `auth:sanctum`.
* **Response Format:** JSON (project envelope)
* **Controller:** `backend/app/Http/Controllers/Api/AuthController.php`
* **Route Definition:** `routes/api.php` (grouped under `api/v1/auth`)
* **Test Definition:** `backend/tests/Feature/TestAuthControllerTest.php` (see Testing section)
* **Permissions:** login = public; register = admin-only; refresh/logout/status = authenticated
* **Caching:** Not recommended for authentication endpoints
* **Error Handling:** Use project envelope `{ success: bool, message: string, data: array|null }`. Validation errors return 422 with details under `data.errors`.

## Overview

AuthController provides the authentication lifecycle used by the frontend: login, token refresh, logout, admin-only registration, and a status check. Per project direction the controller keeps token creation/rotation logic inline (explicit exception), while validation and authorization must use Form Request classes.

## Endpoint

1. POST /api/v1/auth/login — Authenticate a user and return access & refresh tokens.
2. POST /api/v1/auth/register — Create a new user account (admin-only) and return tokens.
3. POST /api/v1/auth/refresh — Exchange a refresh token for new access & refresh tokens.
4. POST /api/v1/auth/logout — Revoke tokens for the authenticated user.
5. GET  /api/v1/auth/status — Return authentication status and current user data.

## Authentication

- `login` — public endpoint; accepts identifier (email | username | whatsapp) + password.
- `register` — admin-only; requests must be authorized by `RegisterRequest::authorize()`.
- `refresh`, `logout`, `status` — protected with `auth:sanctum`.

Implementation note: all controller responses must use the project envelope. Do not expose stack traces.

## Request

### Method & URL

- POST /api/v1/auth/login
- POST /api/v1/auth/register
- POST /api/v1/auth/refresh
- POST /api/v1/auth/logout
- GET  /api/v1/auth/status

### Headers

- Content-Type: application/json
- Accept: application/json
- Authorization: Bearer <token> (for protected endpoints)

### Query Parameters

- None expected by default. Keep endpoints RESTful and param-free for login/register/refresh. If you add `identifier_type` for disambiguation, document it here (string: email|username|whatsapp).

### Request Body

- login
  - username: string (required) — identifier that can be one of: email, username, or whatsapp (phone number). Prefer E.164 for whatsapp.
  - password: string (required)

- register (admin-only)
  - name: string (required)
  - email: string (required, email, unique)
  - username: string (required, unique)
  - whatsapp: string (required, phone format, unique)
  - password: string (required, confirmed, min:8)
  - role: string (optional) — admin can set initial role, default `user`

- refresh
  - refreshToken: string (required) — prefer raw token; controller may accept deprecated `id|token` format and should log a deprecation warning.

### Example Request (login)

```json
{
  "username": "alice@example.com",
  "password": "secret"
}
```

## Response

### Success Response

All responses use the project envelope:

- login (200)
```json
{
  "success": true,
  "message": "Logged in",
  "data": {
    "access_token": "...",
    "refresh_token": "...",
    "token_type": "Bearer",
    "expires_in": null,
    "user": { "id": 1, "name": "Alice", "username": "alice", "email": "alice@example.com", "whatsapp": "+628123456789" }
  }
}
```

- register (201)
```json
{
  "success": true,
  "message": "User created",
  "data": { "user": { /* user object */ }, "accessToken": "...", "refreshToken": "..." }
}
```

- refresh (200)
```json
{ "success": true, "message": "Token refreshed", "data": { "user": { /* user */ }, "accessToken": "...", "refreshToken": "..." } }
```

- logout (200)
```json
{ "success": true, "message": "Logged out", "data": null }
```

- status (200)
```json
{ "success": true, "message": "Status", "data": { "authenticated": true, "user": { /* user */ } } }
```

### Error Responses

- 401 Unauthorized — invalid credentials or token
- 403 Forbidden — inactive account, or forbidden action (e.g., register by non-admin)
- 422 Unprocessable Entity — validation errors; return envelope with `data.errors`

Example validation error:
```json
{
  "success": false,
  "message": "Validation failed",
  "data": { "errors": { "username": ["The username field is required."] } }
}
```

## Data Model

### Properties

- User (partial): id, name, username, email, whatsapp, active

## Menu Structure

- Auth endpoints are API-only; include design notes for admin UI:
  - Admin ▶ Users ▶ Create User (calls POST /api/v1/auth/register)
  - Profile ▶ Logout (calls POST /api/v1/auth/logout)

## Frontend Integration

- Store `access_token` in secure storage (frontend platform-specific).
- Use `refresh_token` to obtain new tokens; prefer sending raw token only.
- Frontend should normalize phone input to E.164 before sending `whatsapp`.
- Prefer `identifier_type` (optional) when UI can provide it to disambiguate logins.
- Note: Deprecated `id|token` refresh format accepted temporarily — backend logs deprecation warning. Frontend should migrate to raw token.

## Caching

- Do not cache authentication endpoints or tokens.

## Role-Based Access / Permissions

- `register` is admin-only. Enforce with `RegisterRequest::authorize()`:
  - returns true only if `$request->user()?->role === App\Enums\UserRole::ADMIN`.
- Controllers should also re-check permission as defense in depth.

## Related Endpoints

- User profile read/update endpoints should maintain the same `user` shape.
- Admin user listing/management endpoints live under `/api/v1/users` (refer to design docs).

## Notes / Additional Information

### Validation examples (Form Request guidance)

`LoginRequest` (`backend/app/Http/Requests/LoginRequest.php`)
```php
public function authorize(): bool { return true; }
public function rules(): array {
    return [
        'username' => ['required','string'],
        'password' => ['required','string'],
        // optional: 'identifier_type' => ['nullable','in:email,username,whatsapp']
    ];
}
```

`RegisterRequest`
```php
public function authorize(): bool {
    return $this->user()?->role === \App\Enums\UserRole::ADMIN;
}
public function rules(): array {
    return [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'username' => 'required|string|unique:users,username',
        'whatsapp' => ['required','string','unique:users,whatsapp','regex:/^[+][0-9]{8,15}$/'],
        'password' => 'required|string|confirmed|min:8',
        'role' => 'nullable|in:user,admin'
    ];
}
```

`RefreshRequest`
```php
public function authorize(): bool { return true; }
public function rules(): array { return ['refreshToken' => 'required|string']; }
```

### Identifier resolution & normalization

- Server resolves `username` in configurable order (recommended default): email → username → whatsapp.
- Normalize whatsapp to E.164 on both client and server before lookup to avoid mismatches.
- Optionally accept an `identifier_type` field to force resolution and avoid ambiguous matches.

### Decisions / Chosen defaults

- Refresh token lifecycle: single-use rotation. On successful refresh the old refresh token is revoked and a new refresh token is issued.
- Token name defaults: `auth_token` and `refresh_token`. Implementations should read `config('auth.token_names.*')` and fall back to these defaults.
- WhatsApp normalization: simple server-side regex enforcing E.164; no external phone library in this change.
- Register defaults: new users are created with `role = user` and `active = true` by default.
- Logging: use the default log channel; generate a `request_id` when missing and include it in log context and response headers for tracing.

### Rate limiting & brute-force protection

- Apply login rate limiting using Laravel RateLimiter:
```php
RateLimiter::for('login', function (Request $request) {
    $key = Str::lower($request->input('username')).'|'.$request->ip();
    return Limit::perMinute(5)->by($key);
});
```
- Return 429 envelope when throttled.

### Token naming, rotation & lifecycle

- Add `auth.token_names` entries to `config/auth.php`:
```php
'token_names' => [
    'auth' => env('AUTH_TOKEN_NAME', 'auth_token'),
    'refresh' => env('REFRESH_TOKEN_NAME', 'refresh_token'),
],
```
- Recommend rotating refresh tokens on use (issue a new refresh token and revoke the old one) to reduce replay risk.
- On logout revoke both access and refresh tokens (`$user->currentAccessToken()->delete()` and revoke stored refresh tokens).

### Refresh token handling

- Prefer raw token strings. For backward compatibility accept `id|token` but extract the token portion and log a deprecation warning.
- Use `PersonalAccessToken::findToken($rawToken)` to look up the token.

### Logging & observability

- Use structured logs (context array) for important events:
  - `login.success` / `login.failure` (include non-sensitive fields: actor_id if available, ip, route)
  - `admin.register.attempt` (actor_id, target_email/username, result)
  - `refresh.deprecated_format` (actor_id or null, note that `id|token` was used)
- Never log passwords or raw tokens.
- Add correlation/requestId to logs for tracing.

### Security considerations

- Ensure all protected routes use `auth:sanctum`.
- Avoid using `env()` outside config files.
- Do not return stack traces or internal errors; always send safe messages and log details server-side.

## Testing

- Place Feature tests in `backend/tests/Feature/TestAuthControllerTest.php`.
- Required test cases:
  - login success and validation failure
  - login via email, username, whatsapp (including E.164 normalization)
  - login blocked/inactive user -> 403
  - rate limit enforcement for login (429)
  - admin register success (201) and register by non-admin -> 403 (envelope)
  - refresh happy path and invalid/deprecated token paths (assert deprecation log when `id|token` used)
  - logout revokes tokens
  - status endpoint returns correct shape
  - envelope compliance assertions for success and error responses

Implementation notes / developer guidance

- Keep controller token logic inline (explicit exception), but keep controller methods small and delegate validation/authorization to Form Requests.
- Use `App\Http\Responses\ApiResponseTrait` (project standard) to produce envelopes.
- Add config entries and documentation for token names and rotation policy.
- Use model factories in tests and `Sanctum::actingAs` or HTTP helpers for authenticated tests.
- Ensure `php artisan test` and `./vendor/bin/pint` pass before merging per repository requirements.

## Actionable recommendations

1. Add `LoginRequest`, `RegisterRequest`, and `RefreshRequest` classes under `backend/app/Http/Requests/`.
2. Update `AuthController` to use those Form Requests and `ApiResponseTrait`.
3. Add `auth.token_names` to `config/auth.php`.
4. Implement refresh token rotation logic and revoke old refresh tokens on rotation/logout.
5. Add Feature tests listed above and run tests.
