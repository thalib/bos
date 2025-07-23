## frontend\app\pages\index.vue

- This page should show the login form to the unauthenticated user.
- On successful login, the user should be redirected to `frontend\app\pages\dashboard.vue`.
- If a `redirect` path is present in the query parameters, the user should be redirected to that path after login.

## The login form will have the following items:

1. Username (can be one of the following fields from the backend database):
   - `username`
   - `email`
   - `whatsapp`
2. Password
3. Remember Me (checkbox)

- The `username` field should accept any of the above values and validate them accordingly.
- Ensure proper error handling and display error messages for invalid credentials.
- Use Bootstrap 5.3 classes for styling the form.