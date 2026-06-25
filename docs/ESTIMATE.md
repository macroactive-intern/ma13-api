Step 1

    Documentation
                1. Write out the Understand.md
                2. Write out the Time Estimate.md
                3. Add the Ai Time estimate to the Estimate.md
                4. Write out the Aproach.md
                                                                                                        120 mins

----------------------------------------------------------------------------------------------------------------

Step 2

    Set up projects
                1. Laravel API repo: ma13-api
                                            - Create Laravel project
                                            - Install Sanctum
                                            - Configure Laravel environment
                                            - Configure Sanctum
                                            - Configure CORS
                2. Next.js repo: ma13-web
                                            - Create Next.js app
                                            - Install test packages
                                            - Configure frontend environment

                                                                                                        60 mins

----------------------------------------------------------------------------------------------------------------

Step 3

    API Implementation Tasks

                1. Add API auth routes
                                    - POST /api/auth/register
                                    - POST /api/auth/login
                                    - POST /api/auth/logout
                                    - GET /api/me
                                    - PUT /api/me/password

                2. Create register endpoint
                                    - Validate name, email, password, and confirmation.
                                    - Require password minimum 8 characters.
                                    - Require unique email.
                                    - Create user.
                                    - Hash password.
                                    - Create Sanctum token.
                                    - Return token with status 201.
                
                3. Create login endpoint
                                    - Validate email and password.
                                    - Find user by email.
                                    - Check password using Hash::check.
                                    - If wrong, return:
                                                    {
                                                        "message": "The provided credentials are incorrect."
                                                    }
                                    - Status 422.
                                    - If correct, create Sanctum token.
                                    - Return token with status 200.
                
                4. Create logout endpoint
                                    - Require auth:sanctum.
                                    - Delete current access token.
                                    - Return status 204.
                                    - Delete only the current token, not all tokens.
                
                5. Create profile endpoint
                                    - Require auth:sanctum.
                                    - Return authenticated user:
                                                                {
                                                                    "id": 1,
                                                                    "name": "...",
                                                                    "email": "..."
                                                                }
                
                6. Create change-password endpoint
                                    - Require auth:sanctum.
                                    - Validate:
                                                current_password
                                                new_password
                                                new_password_confirmation
                                    - Check current password.
                                    - If wrong, return 422.
                                    - If correct, hash and save new password.
                                    - Return success response.
                                                                                                        45 mins

----------------------------------------------------------------------------------------------------------------

Step 4

    API Tests

                1. Test registration
                                    - Registration creates a user.
                                    - Registration returns status 201.
                                    - Registration returns a token.
                                    - Password is hashed, not stored plain.
                
                2. Test login
                                    - Valid credentials return 200.
                                    - Response includes token.
                                    - Invalid credentials return 422.
                                    - Invalid credentials return the required message.
                
                3. Test protected route
                                    - GET /api/me without token returns 401.
                                    - GET /api/me with token returns user profile.

                4. Test logout
                                    - Authenticated logout returns 204.
                                    - Token is deleted.
                                    - After logout, GET /api/me returns 401.
                
                5. Test change password
                                    - Correct current password updates password.
                                    - Wrong current password returns 422.
                                    - New password confirmation must match.
                                    - Old password no longer works after successful update.
                                    - New password works after successful update.
                                                                                                        45 mins

----------------------------------------------------------------------------------------------------------------

Step 5

    Frontend Implementation Tasks

                1. Create auth/token strategy
                                    - Do not use localStorage.
                                    - Do not use JavaScript-readable cookies.
                                    - Store token in an HttpOnly cookie using the Next.js server layer.
                                    - Add helper for setting the auth cookie.
                                    - Add helper for clearing the auth cookie.
                                    - Add helper for reading the auth cookie server-side.

                2. Create Next.js route handlers as proxy layer
                                    - Frontend route handlers can mirror the Laravel API:
                                                                    POST /api/auth/register
                                                                    POST /api/auth/login
                                                                    POST /api/auth/logout
                                                                    GET /api/me
                                                                    PUT /api/me/password
                                    
                                    - Register/login route sends request to Laravel.
                                    - Receives token from Laravel.
                                    - Stores token in HttpOnly cookie.
                                    - Logout route reads token server-side.
                                    - Sends logout request to Laravel.
                                    - Clears HttpOnly cookie.
                                    - Protected route handlers forward token using:
                                                                            Authorization: Bearer <token>
                
                3. Create shared API fetch helper
                                    - Create one central fetch function.
                                    - It handles 401 in one place.
                                    - It redirects to /login when protected API call returns 401.
                                    - Protected pages use this helper.
                                    - Do not repeat if response.status === 401 across every page.
                
                4. Create registration page
                                    - Add form fields:
                                                        Name
                                                        Email
                                                        Password
                                                        Confirm password
                                    - Submit to frontend register route.
                                    - Show validation errors.
                                    - On success, redirect to /profile.

                5. Create login page
                                    - Add form fields:
                                                    Email
                                                    Password
                                    - Submit to frontend login route.
                                    - Show invalid credential error.
                                    - On success, redirect to /profile.
                
                6. Create protected profile page
                                    - Use shared API helper to call GET /api/me.
                                    - If authenticated, show:
                                                        Name
                                                        Email
                                    - If unauthenticated, shared helper redirects to /login.
                                    - Add change-password form.
                                    - Add logout button.
                
                7. Create change-password form
                                    - Fields:
                                            Current password
                                            New password
                                            Confirm new password
                                    - Submit to frontend password route.
                                    - Show 422 error if current password is wrong.
                                    - Show success message if password updates.
                                    - Clear form after success.
                
                8. Create logout button
                                    - Calls frontend logout route.
                                    - Laravel token gets deleted.
                                    - HttpOnly cookie gets cleared.
                                    - User redirects to /login.
                                                                                                        80 mins

----------------------------------------------------------------------------------------------------------------

Step 6

    Frontend Tests

                1. Configure Vitest
                                    - Add vitest.config.ts.
                                    - Configure jsdom.
                                    - Add test setup file.
                                    - Add npm test script.
                2. Test 401 redirect
                                    - Mock protected API call returning 401.
                                    - Confirm shared handler redirects to /login.
                                    - Confirm this behavior is centralised.
                                    - Avoid duplicating redirect logic inside page components.
                3. Optional frontend tests
                                    - Login form submits successfully.
                                    - Register form submits successfully.
                                    - Profile page displays name and email.
                                    - Change-password form displays validation error.
                                    - Logout button calls logout route.
                                                                                                        80 mins

----------------------------------------------------------------------------------------------------------------

Step 7

    Integration / Manual Testing Tasks

                1. Run Laravel API
                2. Run Next.js frontend
                3. Manually test registration flow
                                    - Open /register.
                                    - Submit valid user.
                                    - Confirm redirect to /profile.
                                    - Confirm name/email display.
                4. Manually test login flow
                                    - Open /login.
                                    - Submit valid credentials.
                                    - Confirm redirect to /profile.
                                    - Confirm profile data loads.
                5. Manually test protected route
                                    - Clear auth cookie.
                                    - Visit /profile.
                                    - Confirm redirect to /login.
                6. Manually test logout
                                    - Log in.
                                    - Click logout.
                                    - Confirm redirect to /login.
                                    - Confirm GET /api/me returns 401 after logout.
                7. Manually test password change
                                    - Log in.
                                    - Try wrong current password.
                                    - Confirm 422 error.
                                    - Try correct current password.
                                    - Confirm success.
                                    - Log out.
                                    - Confirm old password fails.
                                    - Confirm new password works.
                8. Verify token storage requirement
                                    - Open browser dev tools.
                                    - Confirm token is not in localStorage.
                                    - Run:
                                            document.cookie
                                    - Confirm token is not visible.
                                    - Confirm auth cookie is HttpOnly.
                                                                                                    120 mins

----------------------------------------------------------------------------------------------------------------

Step 8

    BEFORE-AFTER.md
                                                                                                    30 mins

----------------------------------------------------------------------------------------------------------------

                                                                                                    9.65 hrs

---------------------------------------------------------------------------------------------------------------- 

AI adjusted estimate would be:

Step	Your estimate	My estimate	Notes
Step 1 — Documentation	120 mins	120 mins	Good. UNDERSTANDING, ESTIMATE, AI reconcile, and APPROACH are bigger here.
Step 2 — Project setup	60 mins	75 mins	Two repos, Sanctum, CORS, env config, test packages.
Step 3 — API implementation	45 mins	60 mins	Auth endpoints are simple, but password change + token handling needs care.
Step 4 — API tests	45 mins	60 mins	Logout/token invalidation and password-change tests may take extra debugging.
Step 5 — Frontend implementation	80 mins	130 mins	This is the biggest underestimate. HttpOnly cookie + Next route proxy + central 401 handling takes time.
Step 6 — Frontend tests	80 mins	75 mins	401 redirect test only is manageable; optional tests add time.
Step 7 — Integration/manual testing	120 mins	120 mins	Good estimate. Cross-origin bugs can eat time.
Step 8 — BEFORE-AFTER.md	30 mins	35 mins	Slightly more if you paste clean terminal output.
My final estimate

Focused build estimate: 675 mins
Total: 11 hrs 15 mins

Safer quote

Because this is full-stack auth with two repos, I would quote:

11–12 hours

That gives you room for the likely pain points:

Laravel CORS not allowing the frontend origin first try.
Sanctum token auth vs SPA cookie confusion.
Making the token HttpOnly while still letting the frontend authenticate.
Centralising 401 redirects without duplicating logic.
Getting Vitest to work cleanly with Next.js App Router.