<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => $validated['password'],
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json(['token' => $token], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Hash::check always runs to prevent timing-based email enumeration;
        // the dummy is a real bcrypt hash so password_verify does full work
        // even when $user is null.
        $dummy          = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $passwordMatches = Hash::check($request->password, $user?->password ?? $dummy);

        if (! $user || ! $passwordMatches) {
            return response()->json(
                ['message' => 'The provided credentials are incorrect.'],
                422
            );
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(null, 204);
    }
}
