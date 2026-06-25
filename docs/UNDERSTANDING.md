What is the task asking me to build?

This task is asking me to build a full-stack authentication flow using two separate repos:

A Laravel API repo called ma13-api
A Next.js frontend repo called ma13-web

The Laravel API will handle the real authentication logic using Sanctum. It needs to let users register, log in, log out, view their profile, and change their password.

The Next.js frontend will connect to that Laravel API. It needs to provide pages for registration, login, profile viewing, password changing, and logout.

The task includes:
                    - Laravel project setup
                    - Next.js project setup
                    - Sanctum installation/configuration
                    - CORS configuration
                    - Token storage design
                    - Protected frontend routes
                    - API tests
                    - Frontend tests
                    - Integration behavior between both repos

--------------------------------------------------------------------------------------------------------------------------------------------

What inputs does it take and what does it return/display?

--------------------------------------------------

API side

--------------------------------------------------

POST /api/auth/register

Creates a new user account.

        Request:

                {
                "name": "Alice",
                "email": "alice@example.com",
                "password": "secret123",
                "password_confirmation": "secret123"
                }

        Validation:
                - name is required
                - email is required
                - email must be unique
                - password is required
                - password must be at least 8 characters
                - password_confirmation must match password

        Successful response:

                {
                "token": "1|abc123..."
                }

        Status: 201

--------------------------------------------------

POST /api/auth/login

Logs in an existing user.

        Request:

                {
                "email": "alice@example.com",
                "password": "secret123"
                }
        
        Successful response:
                {
                "token": "1|abc123..."
                }

        Status: 200

        Invalid credentials response:
                                    {
                                    "message": "The provided credentials are incorrect."
                                    }
        Status: 422

--------------------------------------------------

POST /api/auth/logout

Logs out the authenticated user.

        Auth:
            Requires auth:sanctum
        
        Behavior:
                Deletes the current token
        
        Response:
                Empty response

        Status: 204

--------------------------------------------------

GET /api/me

Returns the authenticated user's profile.

        Auth:
            Requires auth:sanctum

        Successful response:
                            {
                            "id": 1,
                            "name": "Alice",
                            "email": "alice@example.com"
                            }
        
        Unauthenticated response:
                                    401
        
--------------------------------------------------

PUT /api/me/password

Changes the authenticated user's password.

        Auth:
            Requires auth:sanctum
        
        Request:
                {
                "current_password": "old-password",
                "new_password": "new-password",
                "new_password_confirmation": "new-password"
                }

        Validation/behavior:
                            current_password is required
                            new_password is required
                            new_password must be at least 8 characters
                            new_password_confirmation must match
                            API must verify the current password before changing it
                            If the current password is wrong, return 422
        
        Successful response:
                            The brief does not specify the exact response body.
                            I will assume a simple success message or empty response is acceptable unless the tests require something specific.
        
--------------------------------------------------------------------------------------------------------------------------------------------

Frontend side

--------------------------------------------------

Registration page

        Displays a form with:
                            Name
                            Email
                            Password
                            Confirm password
        
        On success:
                            The frontend receives a token from the API
                            The token is stored safely
                            The user is redirected to /profile

--------------------------------------------------

Login page

        Displays a form with:
                            Email
                            Password
        
        On success:
                    The frontend receives a token from the API
                    The token is stored safely
                    The user is redirected to /profile
        
        On invalid credentials:
                    The page should show an error message

--------------------------------------------------

Profile page

        Protected page.

        Behavior:
                Calls GET /api/me
                Displays the user's name and email
                Includes a change-password form
                Includes a logout button
        
        If the user is not authenticated:
                                        Redirect to /login
        
--------------------------------------------------

Change-password form

        Displays fields for:
                            Current password
                            New password
                            Confirm new password
        
        On submit:
                            Calls PUT /api/me/password
                            If the current password is wrong, displays the 422 validation error
                            If successful, shows a success message and clears the form

--------------------------------------------------

Logout button

        Behavior:
                Calls POST /api/auth/logout
                Clears the stored token
                Redirects to /login
        
        After logout:
                    GET /api/me should return 401
        
--------------------------------------------------------------------------------------------------------------------------------------------

Important thing I noticed: Sanctum and CORS

Sanctum can support SPA authentication, but it does not mean CORS needs no configuration.

Also, this brief is not using Sanctum's normal cookie-based SPA flow exactly. The API endpoints return tokens:

{
  "token": "1|abc123..."
}

That looks like a Sanctum personal access token flow, where the frontend sends the token using an Authorization: Bearer ... header.

That means I cannot store the token in localStorage, and I also cannot store it in a normal JavaScript-readable cookie.

So the integration-guide note is misleading for this exact task.

What I actually need to configure:
                                    Laravel Sanctum
                                    Laravel auth routes
                                    Laravel CORS config
                                    Allowed frontend origin, probably http://localhost:3000
                                    Allowed methods and headers
                                    Next.js token storage using an HttpOnly cookie or a server-side proxy approach

Sanctum helps with authentication, but I still need to explicitly configure CORS on the Laravel side so the frontend origin can call the API.

--------------------------------------------------------------------------------------------------------------------------------------------

Token storage decision

The token to not be readable from:
                                    document.cookie
                                    localStorage

So I should not store the token in localStorage.

I will store the token in an HttpOnly cookie set by the Next.js app, not directly by browser JavaScript.

The trade-off:

localStorage

Easy to use, but unsafe for this task.

If an attacker gets JavaScript running on the page through XSS, they could read the token from localStorage and use it to impersonate the user.

That would fail the acceptance criteria because the token would be readable by browser JavaScript.

--------------------------------------------------

Normal cookie

Also not acceptable if JavaScript can read it through document.cookie.

--------------------------------------------------

HttpOnly cookie

Better for this task.

Browser JavaScript cannot read the token from an HttpOnly cookie. That means the token is not exposed through localStorage or document.cookie.

Because browser JavaScript cannot read the token, the frontend will need one of these patterns:

1. Use Next.js server actions / route handlers to call the Laravel API with the token
2. Use a small Next.js API proxy layer that reads the HttpOnly cookie server-side and forwards requests to Laravel with the Authorization: Bearer ... header

--------------------------------------------------------------------------------------------------------------------------------------------

Centralised 401 handler

The 401 redirect must be handled in one shared place — not repeated across every fetch call.

So I should not write this logic manually in every page:

if (response.status === 401) {
  redirect('/login')
}

Instead, I will create one shared API helper

That helper will be responsible for calling protected endpoints.

The central behavior will be:
                            Make the request
                            If the response status is 401
                            Clear the auth cookie if needed
                            Redirect the user to /login

The exact implementation may differ depending on whether the call happens from a server component, route handler, or client component, but the decision is the same:
                            401 handling belongs in one shared place
                            Protected pages use that shared helper
                            Adding a fifth protected page should not require writing new 401 redirect logic

--------------------------------------------------------------------------------------------------------------------------------------------

Sanctum SPA note vs token API

Sanctum SPA auth handles cross-origin concerns automatically, but the API returns a token.

Those are two different styles:

Sanctum SPA cookie/session authentication
Sanctum personal access token authentication

The endpoint examples clearly return a token, so I will treat this as a token-based Sanctum implementation.

--------------------------------------------------

Token returned, but token cannot be readable by browser JavaScript

The API returns a token, but the frontend cannot store it in localStorage or JavaScript-readable cookies.

This means the frontend cannot simply call Laravel directly from client-side React and store the token in browser-accessible state.

I will solve this by storing the token in an HttpOnly cookie through the Next.js server layer.

--------------------------------------------------

Exact frontend API shape is not specified

The Next.js frontend should use the real Laravel API endpoints as the source of truth.

That means I should not treat Next.js route handlers as a second fake auth API or duplicate the Laravel API behavior.

The real auth endpoints are owned by Laravel:

POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
GET /api/me
PUT /api/me/password

The Next.js app will point at the Laravel API URL, for example:

--------------------------------------------------

Exact password change success response is not specified

The wrong-password response should be 422, but it does not specify the successful response body.

I will use a simple success response unless tests require a specific shape.

--------------------------------------------------

Exact validation error format is not fully specified

Laravel has a default validation error format. I will use Laravel's normal validation responses unless the brief requires custom response shapes.

--------------------------------------------------

Register/login behavior for already authenticated users is not specified

Say what happens if an already authenticated user visits /login or /register.

I will keep the pages simple. If the user logs in or registers successfully, they go to /profile.

--------------------------------------------------

Whether password change should rotate tokens is not specified

the password should be updated after verifying the current password.

It does not say to revoke existing tokens or force re-login.

I will keep the current token valid after password change unless the brief later requires token rotation.

--------------------------------------------------

Logout failure behavior is not specified

On the frontend, if logout gets a 401, I will still clear the local HttpOnly auth cookie and redirect to /login, because from the user's perspective they should end up logged out.

--------------------------------------------------

CORS exact origin is not specified

The frontend will probably run on:

http://localhost:3000

The Laravel API will probably run on:

http://localhost:8000

I will configure Laravel CORS to allow the frontend origin explicitly.

I will avoid using a wildcard origin because the acceptance criteria specifically says CORS must be explicitly configured to allow requests from the frontend origin.