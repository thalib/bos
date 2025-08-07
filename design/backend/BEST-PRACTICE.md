# Laravel 12 API Best Practices

Essential rules for building robust REST APIs with Laravel 12.

---

## 1. Use Enums for State, Not Boolean Flags

**Compliance:** N/A  
**Implemented in:** (Model/migration files, not in provided files)  
**Should implement:** Models, migrations

Replace boolean fields with enums for clearer business logic.

```php
// migration
$table->enum('status', ['active', 'inactive', 'deleted'])->default('active');
```

## 2. Always Use API Resources for Responses

**Compliance:** ❌ Not compliant  
**Implemented in:** None (should be in `ApiResourceController.php`)  
**Should implement:** `ApiResourceController.php` should use API Resource classes for all responses instead of returning Eloquent models directly.

Never expose Eloquent models directly. Control your API output.

```php
// app/Http/Resources/UserResource.php
public function toArray($request) {
    return [
        'id' => $this->id,
        'name' => $this->name,
        'email' => $this->email,
    ];
}
```

## 3. Use UUIDs for Public IDs

**Compliance:** N/A  
**Implemented in:** (Model/migration files, not in provided files)  
**Should implement:** Models, migrations

```php
$table->uuid('id')->primary();
```

## 4. Paginate All List Endpoints

**Compliance:** ✅ Compliant  
**Implemented in:** `ApiResourceController.php`  
**Should implement:** `ApiResourceController.php`

```php
return UserResource::collection(User::paginate(20));
```

## 5. Version Your API from Day 1

**Compliance:** ✅ Compliant  
**Implemented in:** `ApiResourceServiceProvider.php`  
**Should implement:** `ApiResourceServiceProvider.php`

```php
Route::prefix('api/v1')->group(function () {
    Route::apiResource('users', UserController::class);
});
```

## 6. Standardize Error Responses

**Compliance:** ⚠️ Partial compliance  
**Implemented in:** `ApiResponseTrait.php`  
**Should implement:** `ApiResponseTrait.php` should align field names (`error: true` instead of `success: false`) and always include `traceId`.

```php
return response()->json([
    'error' => true,
    'message' => 'Invalid request',
    'traceId' => request()->header('X-Trace-Id'),
], 400);
```

## 7. Use Soft Deletes

**Compliance:** N/A  
**Implemented in:** (Model files, not in provided files)  
**Should implement:** Models

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model {
    use SoftDeletes;
}
```

## 8. Add Trace IDs to All Requests

**Compliance:** ❌ Not compliant  
**Implemented in:** None  
**Should implement:** Middleware (not provided), and `ApiResponseTrait.php` should include `traceId` in all responses.

Create middleware to generate unique request IDs for debugging.

```php
// In middleware
$traceId = (string) Str::uuid();
Log::info('Request', ['traceId' => $traceId]);
return response($data)->header('X-Trace-Id', $traceId);
```

## 9. Rate Limit All Public APIs

**Compliance:** ❌ Not compliant  
**Implemented in:** None  
**Should implement:** `ApiResourceServiceProvider.php` should add `throttle` middleware to API routes.

```php
Route::middleware('throttle:60,1')->group(function () {
    // API routes
});
```

## 10. Never Return Null for Missing Resources

**Compliance:** ✅ Compliant  
**Implemented in:** `ApiResourceController.php`  
**Should implement:** `ApiResourceController.php`

```php
$user = User::find($id);
if (!$user) {
    abort(404, 'User not found');
}
```

## 11. Use ETag for Caching

**Compliance:** ❌ Not compliant  
**Implemented in:** None  
**Should implement:** `ApiResponseTrait.php` or controller responses should add ETag headers.

```php
$etag = md5(json_encode($data));
return response($data)->header('ETag', $etag);
```

## 12. Document Your API

**Compliance:** N/A  
**Implemented in:** (Documentation tools, not in provided files)  
**Should implement:** API documentation tools

Use Laravel OpenAPI/Swagger tools for auto-generated documentation.

## 13. Never Leak Internal Errors

**Compliance:** ✅ Compliant  
**Implemented in:** `ApiResourceController.php`  
**Should implement:** `ApiResourceController.php`

```php
// In app/Exceptions/Handler.php
public function render($request, Throwable $exception) {
    if (app()->environment('production')) {
        return response()->json([
            'error' => true, 
            'message' => 'Server error'
        ], 500);
    }
    return parent::render($request, $exception);
}
```

## 14. Use Laravel Sanctum for Authentication

**Compliance:** ✅ Compliant  
**Implemented in:** `ApiResourceServiceProvider.php`  
**Should implement:** `ApiResourceServiceProvider.php`

Protect all endpoints with `auth:sanctum` middleware.

## 15. Validate All Input

**Compliance:** ✅ Compliant  
**Implemented in:** `ApiResourceController.php`  
**Should implement:** `ApiResourceController.php`

Use Form Request classes for validation.

```php
// app/Http/Requests/CreateUserRequest.php
public function rules() {
    return [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
    ];
}
```

---

**Follow these rules for maintainable, secure, and scalable Laravel APIs.**
