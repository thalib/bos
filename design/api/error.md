## Error

```json
{
    "success": false,
    "message": "<User-friendly error message>",
    "error": {
        "code": "<ERROR_CODE>",
        "details": [ /* array of error details */ ]
    }
}
```

- **`error.code`**: Machine-readable error code (string)
- **`error.details`**: Optional array of error details

### Response Codes

| Code                  | HTTP | Description                        |
|-----------------------|------|------------------------------------|
| OK                    | 200  | Operstion successfully completed   |
| BAD_REQUEST           | 400  | Invalid parameters                 |
| UNAUTHORIZED          | 401  | Authentication required            |
| FORBIDDEN             | 403  | Access denied                      |
| NOT_FOUND             | 404  | Resource endpoint not found        |
| METHOD_NOT_ALLOWED    | 405  | Method not supported               |
| CONFLICT              | 409  | Resource conflict detected         |
| UNPROCESSABLE_ENTITY  | 422  | Validation error (syntax/fields)   |
| TOO_MANY_REQUESTS     | 429  | Too many requests                  |
| INTERNAL_SERVER_ERROR | 500  | Server error                       |
| SERVICE_UNAVAILABLE   | 503  | Server overloaded/maintenance      |

**Note:** Invalid request params (pagination, sort, filter, search) do not return errors. Defaults are used and notifications are included in the response.
