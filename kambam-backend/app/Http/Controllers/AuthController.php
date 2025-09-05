<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Delete existing tokens
        $user->tokens()->delete();

        // Create access token (1 hour)
        $accessToken = $user->createToken('access_token', ['*'], now()->addHour());
        
        // Create refresh token (14 days)
        $refreshToken = $user->createToken('refresh_token', ['refresh'], now()->addDays(14));

        return response()->json([
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string'
        ]);

        // Find the token
        [$id, $token] = explode('|', $request->refresh_token);
        $refreshToken = PersonalAccessToken::find($id);

        if (!$refreshToken || !Hash::check($token, $refreshToken->token)) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Invalid refresh token.']
            ]);
        }

        // Check if token has refresh ability
        if (!$refreshToken->can('refresh')) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Token cannot be used for refresh.']
            ]);
        }

        // Check if token is expired
        if ($refreshToken->created_at->addDays(14)->isPast()) {
            $refreshToken->delete();
            throw ValidationException::withMessages([
                'refresh_token' => ['Refresh token has expired.']
            ]);
        }

        $user = $refreshToken->tokenable;
        
        // Delete all access tokens
        $user->tokens()->where('name', 'access_token')->delete();

        // Create new access token
        $accessToken = $user->createToken('access_token', ['*'], now()->addHour());

        return response()->json([
            'access_token' => $accessToken->plainTextToken,
            'user' => $user
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
