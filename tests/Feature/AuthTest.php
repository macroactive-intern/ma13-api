<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    public function test_registration_creates_a_user(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Alice',
            'email'                 => 'alice@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
    }

    public function test_registration_returns_201(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Alice',
            'email'                 => 'alice@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201);
    }

    public function test_registration_returns_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Alice',
            'email'                 => 'alice@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertJsonStructure(['token']);
    }

    public function test_registration_hashes_password(): void
    {
        $this->postJson('/api/auth/register', [
            'name'                  => 'Alice',
            'email'                 => 'alice@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $user = User::where('email', 'alice@example.com')->first();

        $this->assertNotEquals('secret123', $user->password);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_registration_fails_when_password_confirmation_does_not_match(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Alice',
            'email'                 => 'alice@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422);
    }

    public function test_registration_fails_when_email_is_already_taken(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Alice',
            'email'                 => 'alice@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422);
    }

    public function test_registration_fails_when_password_is_too_short(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Alice',
            'email'                 => 'alice@example.com',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
    }

    public function test_registration_fails_when_email_is_invalid(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Alice',
            'email'                 => 'not-an-email',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Login
    // -------------------------------------------------------------------------

    public function test_login_with_valid_credentials_returns_200(): void
    {
        User::factory()->create([
            'email'    => 'alice@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'alice@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
    }

    public function test_login_response_includes_token(): void
    {
        User::factory()->create([
            'email'    => 'alice@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'alice@example.com',
            'password' => 'secret123',
        ]);

        $response->assertJsonStructure(['token']);
    }

    public function test_login_with_invalid_credentials_returns_422(): void
    {
        User::factory()->create([
            'email'    => 'alice@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'alice@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_with_invalid_credentials_returns_required_message(): void
    {
        User::factory()->create([
            'email'    => 'alice@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'alice@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertJson(['message' => 'The provided credentials are incorrect.']);
    }

    // -------------------------------------------------------------------------
    // Protected route
    // -------------------------------------------------------------------------

    public function test_get_me_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    public function test_get_me_with_token_returns_user_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/me');

        $response->assertStatus(200);
        $response->assertJson([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ]);
    }

    // -------------------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------------------

    public function test_logout_returns_204(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token');

        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/auth/logout');

        $response->assertStatus(204);
    }

    public function test_logout_deletes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token');

        $this->withToken($token->plainTextToken)->postJson('/api/auth/logout');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_get_me_after_logout_returns_401(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token');
        $plain = $token->plainTextToken;

        $this->withToken($plain)->postJson('/api/auth/logout');

        // The auth guard caches the resolved user across requests in the same
        // test process. Forget it so the next request re-resolves from the token.
        $this->app['auth']->forgetGuards();

        $response = $this->withToken($plain)->getJson('/api/me');

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Change password
    // -------------------------------------------------------------------------

    public function test_correct_current_password_updates_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        $this->actingAs($user, 'sanctum')->putJson('/api/me/password', [
            'current_password'          => 'oldpassword',
            'new_password'              => 'newpassword',
            'new_password_confirmation' => 'newpassword',
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword', $user->password));
    }

    public function test_wrong_current_password_returns_422(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/me/password', [
            'current_password'          => 'wrongpassword',
            'new_password'              => 'newpassword',
            'new_password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_new_password_confirmation_must_match(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/me/password', [
            'current_password'          => 'oldpassword',
            'new_password'              => 'newpassword',
            'new_password_confirmation' => 'differentpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_old_password_no_longer_works_after_change(): void
    {
        $user = User::factory()->create([
            'email'    => 'alice@example.com',
            'password' => Hash::make('oldpassword'),
        ]);

        $this->actingAs($user, 'sanctum')->putJson('/api/me/password', [
            'current_password'          => 'oldpassword',
            'new_password'              => 'newpassword',
            'new_password_confirmation' => 'newpassword',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'alice@example.com',
            'password' => 'oldpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_new_password_works_after_change(): void
    {
        $user = User::factory()->create([
            'email'    => 'alice@example.com',
            'password' => Hash::make('oldpassword'),
        ]);

        $this->actingAs($user, 'sanctum')->putJson('/api/me/password', [
            'current_password'          => 'oldpassword',
            'new_password'              => 'newpassword',
            'new_password_confirmation' => 'newpassword',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'alice@example.com',
            'password' => 'newpassword',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token']);
    }
}
