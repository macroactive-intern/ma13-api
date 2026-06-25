 ____________________________________________________________________________________________________________________________

 Next.js

 ____________________________________________________________________________________________________________________________

 
 RUN  v4.1.9 C:/Users/mccor/Desktop/Projects/MacroActive/MA13/ma13-web

 ✓ __tests__/api-client.test.ts (3 tests) 4ms
 ✓ __tests__/LogoutButton.test.tsx (1 test) 120ms
 ✓ __tests__/LoginForm.test.tsx (2 tests) 700ms
     ✓ redirects to /profile on successful login  326ms
     ✓ shows the invalid credentials message on 422  371ms
 ✓ __tests__/ChangePasswordForm.test.tsx (2 tests) 1047ms
     ✓ shows error when current password is wrong  416ms
     ✓ shows success message and clears the form on success  629ms
 ✓ __tests__/RegisterForm.test.tsx (2 tests) 1138ms
     ✓ redirects to /profile on successful registration  456ms
     ✓ shows a field error when email is already taken  681ms

 Test Files  5 passed (5)
      Tests  10 passed (10)
   Start at  11:32:58
   Duration  2.44s (transform 195ms, setup 445ms, import 998ms, tests 3.01s, environment 4.03s)

 ____________________________________________________________________________________________________________________________

 laravel

 ____________________________________________________________________________________________________________________________

PASS  Tests\Unit\ExampleTest
  ✓ that true is true                                                                                                                      0.01s  

   PASS  Tests\Feature\AuthTest
  ✓ registration creates a user                                                                                                            0.25s  
  ✓ registration returns 201                                                                                                               0.01s  
  ✓ registration returns token                                                                                                             0.01s  
  ✓ registration hashes password                                                                                                           0.02s  
  ✓ login with valid credentials returns 200                                                                                               0.03s  
  ✓ login response includes token                                                                                                          0.01s  
  ✓ login with invalid credentials returns 422                                                                                             0.01s  
  ✓ login with invalid credentials returns required message                                                                                0.02s  
  ✓ get me without token returns 401                                                                                                       0.02s  
  ✓ get me with token returns user profile                                                                                                 0.01s  
  ✓ logout returns 204                                                                                                                     0.01s  
  ✓ logout deletes token                                                                                                                   0.01s  
  ✓ get me after logout returns 401                                                                                                        0.01s  
  ✓ correct current password updates password                                                                                              0.01s  
  ✓ wrong current password returns 422                                                                                                     0.01s  
  ✓ new password confirmation must match                                                                                                   0.02s  
  ✓ old password no longer works after change                                                                                              0.02s  
  ✓ new password works after change                                                                                                        0.02s  

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response                                                                                          0.04s  

  Tests:    20 passed (23 assertions)
  Duration: 0.73s