<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'message' => 'Email e senha são obrigatórios',
                'details' => $validator->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Credenciais inválidas',
                'message' => 'Email ou senha incorretos'
            ], 401);
        }

        $tokens = $user->createToken($request->ip(), $request->userAgent());

        return response()->json([
            'message' => 'Login realizado com sucesso',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'tokens' => $tokens
        ], 200);
    }

    public function refresh(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'message' => 'Refresh token é obrigatório',
                'details' => $validator->errors()
            ], 400);
        }

        $user = User::whereHas('tokens', function ($query) use ($request) {
            $query->where('refresh_token_hash', hash('sha256', $request->refresh_token))
                  ->where('refresh_expires_at', '>', now());
        })->first();

        if (!$user) {
            return response()->json([
                'error' => 'Refresh token inválido ou expirado',
                'message' => 'O refresh token fornecido é inválido ou expirou'
            ], 401);
        }

        $tokens = $user->refreshToken($request->refresh_token);

        if (!$tokens) {
            return response()->json([
                'error' => 'Falha ao renovar token',
                'message' => 'Não foi possível renovar o token de acesso'
            ], 401);
        }

        return response()->json([
            'message' => 'Token renovado com sucesso',
            'tokens' => $tokens
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return response()->json([
                'error' => 'Token ausente',
                'message' => 'É necessário fornecer um token de acesso válido'
            ], 401);
        }

        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Usuário não autenticado',
                'message' => 'Usuário não encontrado'
            ], 401);
        }

        $revoked = $user->revokeToken($token);

        if (!$revoked) {
            return response()->json([
                'error' => 'Token inválido',
                'message' => 'O token fornecido é inválido'
            ], 401);
        }

        return response()->json([
            'message' => 'Logout realizado com sucesso'
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Usuário não autenticado',
                'message' => 'Usuário não encontrado'
            ], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ], 200);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }
}