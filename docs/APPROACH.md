Goal

I am building a full-stack authentication flow with two separate repos:

ma13-api — Laravel API using Sanctum
ma13-web — Next.js frontend using the Laravel API

The Laravel API will handle account creation, login, logout, profile lookup, and password changes.

The Next.js frontend will provide registration, login, profile, password change, and logout screens.

The important part of this task is the integration between the two repos:

The frontend must authenticate against the real Laravel API.
CORS must allow the frontend origin.
The auth token must not be stored in localStorage.
The auth token must not be readable through document.cookie.
Protected-page 401 redirects must be handled in one shared place.
Main architecture decision

The Laravel brief shows register and login returning a Sanctum token:

{
  "token": "1|abc123..."
}

That means I will treat this as Sanctum personal access token authentication.

However, the frontend requirement says:

The token must not be readable via document.cookie or localStorage from browser JavaScript.

Because of that, the browser client cannot store the token in localStorage, and it also cannot store it in a normal JavaScript-readable cookie.

My approach is:

Laravel returns a Sanctum token to the Next.js server layer.
Next.js stores that token in an HttpOnly cookie.
Browser JavaScript cannot read the token.
Next.js route handlers read the HttpOnly cookie server-side.
Next.js forwards protected requests to Laravel with:
Authorization: Bearer <token>

This gives the frontend a safer token storage strategy while still using the token-based API required by the brief.

API side: Laravel repo

Repo name:

ma13-api
Libraries/packages
Laravel

Used for the backend API.

Laravel Sanctum

Used for issuing and validating API tokens.

Install:

composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

Sanctum will provide the personal_access_tokens table and the auth:sanctum middleware.

Laravel validation

Used for request validation on register, login, and password change.

Laravel hashing

Used through Hash::make() and Hash::check() to safely store and verify passwords.

API data model

I will use Laravel's default users table and Sanctum's personal_access_tokens table.

users table

Laravel's default users table already gives most of what this task needs.

Important columns:

Column	Type	Notes
id	bigint unsigned	Primary key
name	varchar	Required for registration
email	varchar	Required, unique
email_verified_at	timestamp nullable	Not required for this task
password	varchar	Hashed password
remember_token	varchar nullable	Default Laravel field
created_at	timestamp nullable	Created timestamp
updated_at	timestamp nullable	Updated timestamp

Constraints:

email should be unique.
password must always be hashed.
Plain-text passwords must never be stored.
personal_access_tokens table

Created by Sanctum.

Important columns:

Column	Type	Notes
id	bigint unsigned	Primary key
tokenable_type	varchar	Model type, usually App\Models\User
tokenable_id	bigint unsigned	User ID
name	varchar	Token name
token	varchar	Hashed token
abilities	text nullable	Token permissions
last_used_at	timestamp nullable	Last used time
expires_at	timestamp nullable	Optional expiry
created_at	timestamp nullable	Created timestamp
updated_at	timestamp nullable	Updated timestamp

Important behavior:

Laravel Sanctum stores a hashed version of the token.
The plain token is only returned once when it is created.
Logout should delete the current token only.
User model

The User model needs Sanctum token support:

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
}
API routes

Routes will live in:

routes/api.php
Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [MeController::class, 'show']);
    Route::put('/me/password', [MeController::class, 'updatePassword']);
});
API endpoints
POST /api/auth/register

Creates a new user and returns a Sanctum token.

Request:

{
  "name": "Alice",
  "email": "alice@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}

Validation:

name required
email required
email valid email format
email unique in users
password required
password minimum 8 characters
password_confirmation must match

Implementation steps:

Validate request.
Create user.
Hash password with Hash::make().
Create Sanctum token with $user->createToken('auth-token').
Return token with status 201.

Response:

{
  "token": "1|abc123..."
}
POST /api/auth/login

Logs in an existing user and returns a Sanctum token.

Request:

{
  "email": "alice@example.com",
  "password": "secret123"
}

Implementation steps:

Validate request.
Find user by email.
If user does not exist, return 422.
Check password with Hash::check().
If password is wrong, return 422.
If correct, create Sanctum token.
Return token with status 200.

Invalid credentials response:

{
  "message": "The provided credentials are incorrect."
}

Important decision:

I will use the same invalid credentials message whether the email is missing or the password is wrong.
This avoids exposing which emails are registered.
POST /api/auth/logout

Logs out the authenticated user.

Auth:

auth:sanctum

Implementation steps:

Get the authenticated user's current access token.
Delete only the current token.
Return 204 No Content.

Important decision:

I will delete only the current token, not every token belonging to the user.
This matches the brief wording: "invalidates the token."
GET /api/me

Returns the authenticated user's profile.

Auth:

auth:sanctum

Response:

{
  "id": 1,
  "name": "Alice",
  "email": "alice@example.com"
}

Implementation steps:

Require authenticated user.
Return only the safe user fields:
id
name
email

Important decision:

I will not return password, remember_token, timestamps, or token data.
PUT /api/me/password

Changes the authenticated user's password.

Auth:

auth:sanctum

Request:

{
  "current_password": "old-password",
  "new_password": "new-password",
  "new_password_confirmation": "new-password"
}

Validation:

current_password required
new_password required
new_password minimum 8 characters
new_password_confirmation must match new_password

Implementation steps:

Validate request fields.
Use Hash::check() to compare current_password against the authenticated user's stored password.
If the current password is wrong, return 422.
If correct, hash the new password.
Save the user.
Return a success response.

Wrong current password response:

{
  "message": "The current password is incorrect."
}

Status:

422

Successful response:

{
  "message": "Password updated successfully."
}

Important decision:

I will not log the user out after changing the password unless the brief later asks for that.
The current token remains valid after password change.
The old password should stop working for future logins.
The new password should work for future logins.
Laravel CORS approach

The frontend will likely run on:

http://localhost:3000

The Laravel API will likely run on:

http://localhost:8000

The acceptance criteria says:

CORS is explicitly configured on the Laravel side to allow requests from the frontend origin.

So I will explicitly configure the Laravel CORS settings to allow the Next.js origin.

The config should allow:

http://localhost:3000

Allowed paths:

'paths' => ['api/*', 'sanctum/csrf-cookie'],

Allowed methods:

'allowed_methods' => ['GET', 'POST', 'PUT', 'OPTIONS'],

Allowed headers:

'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With'],

Allowed origins:

'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],

Environment variable:

FRONTEND_URL=http://localhost:3000

Important note:

The integration guide says Sanctum handles cross-origin concerns automatically, but I am not treating that as fully true for this task.

Sanctum helps with authentication, but the Laravel API still needs CORS configured so the frontend origin can make requests.

Frontend side: Next.js repo

Repo name:

ma13-web
Libraries/packages
Next.js

Used for the frontend app and route handlers.

TypeScript

Used for safer code and clearer types.

Tailwind CSS

Used for basic page styling.

Vitest

Used for frontend tests.

React Testing Library

Used for testing React components and behavior from the user's point of view.

user-event

Used for realistic form interactions in tests.

jsdom

Used to provide a browser-like test environment.

Frontend component structure

Planned structure:

ma13-web/
  app/
    login/
      page.tsx
    register/
      page.tsx
    profile/
      page.tsx
    api/
      auth/
        register/
          route.ts
        login/
          route.ts
        logout/
          route.ts
      me/
        route.ts
      me/
        password/
          route.ts
  components/
    LoginForm.tsx
    RegisterForm.tsx
    ChangePasswordForm.tsx
    LogoutButton.tsx
  lib/
    auth-cookie.ts
    api-client.ts
    laravel-api.ts
  test/
    setup.ts
  vitest.config.ts

The exact file names may change slightly while building, but the structure will stay the same:

Pages display UI.
Components handle forms.
Route handlers talk to Laravel.
Shared helpers handle auth cookies and protected fetch behavior.
Frontend route handlers / proxy layer

Because the token must not be readable by browser JavaScript, the frontend should not store the token in localStorage.

Instead, I will use Next.js route handlers as a small proxy layer.

The browser talks to Next.js:

Browser -> Next.js route handler

Next.js talks to Laravel:

Next.js route handler -> Laravel API

This allows the token to stay in an HttpOnly cookie.

Frontend-facing routes

The frontend route handlers will mirror the Laravel API:

POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
GET /api/me
PUT /api/me/password

These are not the real auth implementation. They are proxy routes that call the Laravel API.

POST /api/auth/register

Frontend behavior:

Browser submits registration form to Next.js route handler.
Next.js forwards the request body to Laravel POST /api/auth/register.
Laravel returns a token.
Next.js stores the token in an HttpOnly cookie.
Next.js returns success to the browser.
Browser redirects to /profile.
POST /api/auth/login

Frontend behavior:

Browser submits login form to Next.js route handler.
Next.js forwards the request body to Laravel POST /api/auth/login.
Laravel returns a token.
Next.js stores the token in an HttpOnly cookie.
Next.js returns success to the browser.
Browser redirects to /profile.
POST /api/auth/logout

Frontend behavior:

Browser calls Next.js logout route.
Next.js reads the token from the HttpOnly cookie server-side.
Next.js calls Laravel POST /api/auth/logout with the bearer token.
Laravel deletes the current token.
Next.js clears the HttpOnly cookie.
Browser redirects to /login.

Important edge case:

If Laravel returns 401 during logout, Next.js should still clear the cookie and send the user to /login.
GET /api/me

Frontend behavior:

Next.js route handler reads token from HttpOnly cookie.
If no token exists, return 401.
If token exists, call Laravel GET /api/me.
Forward Laravel's response to the frontend.
PUT /api/me/password

Frontend behavior:

Browser submits password change form.
Next.js route handler reads token from HttpOnly cookie.
If no token exists, return 401.
If token exists, forward request to Laravel PUT /api/me/password.
Return Laravel's success or validation error response.
Token storage strategy
What I will not use

I will not use:

localStorage

Reason:

JavaScript can read localStorage.
If there is an XSS issue, the attacker can steal the token.
This fails the acceptance criteria.

I will also not use a normal JavaScript-readable cookie.

Reason:

JavaScript can read it through document.cookie.
This also fails the acceptance criteria.
What I will use

I will store the token in an HttpOnly cookie.

Cookie settings:

{
  httpOnly: true,
  secure: process.env.NODE_ENV === 'production',
  sameSite: 'lax',
  path: '/',
}

In local development, secure will be false because local development usually runs over HTTP.

In production, secure should be true because cookies should only be sent over HTTPS.

Trade-off

The trade-off is that client-side JavaScript cannot directly read the token.

That is good for security, but it means client components cannot directly attach:

Authorization: Bearer <token>

So the app needs a server-side layer.

That is why I am using Next.js route handlers to read the HttpOnly cookie and forward protected requests to Laravel.

Centralised 401 redirect strategy

The brief requires:

The 401 redirect must be handled in one shared place — not repeated across every fetch call.

I will create one shared helper, for example:

lib/api-client.ts

This helper will be used by protected frontend code.

Concept:

export async function apiFetch(path: string, options?: RequestInit) {
  const response = await fetch(path, options)

  if (response.status === 401) {
    redirect('/login')
  }

  return response
}

Protected pages will use this helper instead of calling fetch() directly.

Example:

const response = await apiFetch('/api/me')
const user = await response.json()

Important behavior:

The profile page should not manually check for 401.
Any future protected page should use the same helper.
If I add a fifth protected page, I should not need to rewrite 401 handling.
The only thing the new protected page needs to do is use the shared helper.
Frontend pages
/register

Displays:

Name field
Email field
Password field
Confirm password field
Submit button

Behavior:

User submits form.
Form sends request to POST /api/auth/register.
If successful, token is stored by the route handler in an HttpOnly cookie.
User redirects to /profile.
If validation fails, show error message.
/login

Displays:

Email field
Password field
Submit button

Behavior:

User submits form.
Form sends request to POST /api/auth/login.
If successful, token is stored by the route handler in an HttpOnly cookie.
User redirects to /profile.
If credentials are wrong, show the API's 422 error message.
/profile

Protected page.

Behavior:

Page calls GET /api/me using the central apiFetch() helper.
If the request returns 401, the helper redirects to /login.
If authenticated, display:
Name
Email
Show change-password form.
Show logout button.
Change-password flow

The change-password form lives on the profile page.

Fields:

Current password
New password
Confirm new password

Behavior:

User submits form.
Frontend calls PUT /api/me/password.
Next.js route handler forwards request to Laravel with bearer token.
Laravel verifies the current password.
If wrong, Laravel returns 422.
Frontend displays the error message.
If correct, Laravel updates the password.
Frontend displays success message and clears the form.

Important edge cases:

Wrong current password should not update anything.
New password confirmation must match.
New password must be at least 8 characters.
Old password should fail after successful change.
New password should work after successful change.
Logout flow

The logout button will call:

POST /api/auth/logout

Behavior:

Next.js reads the token from the HttpOnly cookie.
Next.js calls Laravel logout endpoint with bearer token.
Laravel deletes the current token.
Next.js clears the HttpOnly cookie.
Frontend redirects to /login.

After logout:

Calling GET /api/me should return 401.
Visiting /profile should redirect to /login.
Testing approach

I want to cover both repos with tests.

API tests

I will write Laravel feature tests for the required API behavior.

Register test

Covers:

User can register.
Response status is 201.
Response contains token.
User exists in database.
Password is hashed.
Login test

Covers:

Valid credentials return 200.
Response contains token.
Invalid credentials return 422.
Invalid credentials return:
{
  "message": "The provided credentials are incorrect."
}
Protected route test

Covers:

GET /api/me without token returns 401.
GET /api/me with token returns the user's id, name, and email.
Logout test

Covers:

Authenticated logout returns 204.
Current token is deleted.
After logout, the same token can no longer access GET /api/me.
Change-password test

Covers:

Correct current password updates the password.
Wrong current password returns 422.
Wrong current password does not change the password.
New password confirmation must match.
Old password no longer works after successful password change.
New password works after successful password change.
Frontend tests

I will configure Vitest, React Testing Library, user-event, and jsdom.

Minimum required frontend test:

Centralised 401 redirect test

Covers:

A protected API request returns 401.
The shared helper redirects to /login.
The redirect logic lives in the shared helper, not each page.

Useful optional tests if time allows:

Login form submits successfully.
Register form submits successfully.
Profile page displays name and email.
Change-password form displays an error on wrong current password.
Logout button calls logout route.
Manual integration testing

I will manually test the complete flow with both apps running.

Laravel:

php artisan serve

Expected API origin:

http://localhost:8000

Next.js:

npm run dev

Expected frontend origin:

http://localhost:3000

Manual test cases:

Register a new user.
Confirm redirect to /profile.
Confirm profile displays name and email.
Log out.
Confirm redirect to /login.
Confirm /profile redirects to /login when unauthenticated.
Log in with valid credentials.
Confirm redirect to /profile.
Try changing password with the wrong current password.
Confirm 422 error.
Change password with correct current password.
Log out.
Confirm old password fails.
Confirm new password works.
Open browser dev tools.
Confirm token is not in localStorage.
Run document.cookie.
Confirm token is not visible.
Confirm the auth cookie is HttpOnly.
Edge cases
Duplicate registration email

If someone registers with an email that already exists, the API should return Laravel's validation error response.

Invalid login email

If the email does not exist, return:

{
  "message": "The provided credentials are incorrect."
}

Status:

422

This avoids exposing whether an email exists.

Invalid login password

If the password is wrong, return the same invalid credentials response.

Missing token on protected route

If there is no token, protected endpoints should return 401.

On the frontend, the shared API helper should redirect protected pages to /login.

Expired, deleted, or invalid token

If the token exists in the HttpOnly cookie but Laravel rejects it, the frontend should treat it as unauthenticated.

Behavior:

Laravel returns 401.
Shared 401 handler redirects to /login.
The cookie may be cleared as part of the unauthenticated handling.
Logout with missing/invalid token

If logout is requested but the token is missing or invalid, the frontend should still clear the local HttpOnly cookie and redirect to /login.

Reason:

The user is effectively logged out already.
Keeping a bad local cookie would cause repeated failed requests.
Wrong current password

If PUT /api/me/password receives the wrong current password:

Return 422.
Do not update the password.
Do not revoke the token.
Display the error on the frontend.
Password confirmation mismatch

If new_password_confirmation does not match new_password:

Return Laravel validation error.
Do not update the password.
Password too short

If the new password is shorter than 8 characters:

Return Laravel validation error.
Do not update the password.
Profile page loaded without auth

If the user navigates directly to /profile with no valid auth cookie:

GET /api/me returns 401.
Shared helper redirects to /login.
CORS preflight request

Because the frontend and API are on different origins, the browser may send an OPTIONS preflight request.

Laravel CORS config must allow the frontend origin, required methods, and required headers.

Token visible in browser dev tools

The token should not appear in:

localStorage

The token should not appear in:

document.cookie

It may be visible in the browser's Application/Cookies panel as an HttpOnly cookie, but it should be marked HttpOnly and unavailable to JavaScript.

Decisions made from ambiguous parts of the brief
1. Sanctum auth style

The brief talks about Sanctum SPA auth, but the endpoint examples return tokens.

Decision:

I will use Sanctum personal access tokens.
I will not use a pure session-cookie Sanctum SPA flow.

Reason:

The required API response explicitly returns { "token": "..." }.
2. Token storage

Decision:

Store the token in an HttpOnly cookie through the Next.js server layer.

Reason:

The token cannot be readable through localStorage.
The token cannot be readable through document.cookie.
3. Frontend-to-API communication

Decision:

Use Next.js route handlers as a backend-for-frontend proxy.

Reason:

Client-side JavaScript cannot read an HttpOnly token.
Server-side route handlers can read the cookie and add the bearer token to Laravel requests.
4. CORS

Decision:

Explicitly configure Laravel CORS to allow http://localhost:3000.

Reason:

The acceptance criteria requires explicit CORS config.
Sanctum does not remove the need to configure CORS for the frontend origin.
5. Password change success response

Decision:

Return:
{
  "message": "Password updated successfully."
}

Reason:

The brief specifies wrong-password behavior but does not specify the exact success response.
6. Logout scope

Decision:

Logout deletes only the current token.

Reason:

The brief says logout invalidates "the token".
It does not say to revoke all of the user's tokens.
7. Password change token behavior

Decision:

Password change does not automatically revoke the current token.

Reason:

The brief does not require token revocation after password change.
The task only requires verifying current password and updating to the new password.