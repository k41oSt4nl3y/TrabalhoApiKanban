<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return response()->json([
                'error' => 'Token de acesso ausente',
                'message' => 'É necessário fornecer um token de acesso válido no header Authorization'
            ], 401);
        }

        $user = User::findByToken($token);

        if (!$user) {
            return response()->json([
                'error' => 'Token inválido ou expirado',
                'message' => 'O token fornecido é inválido ou expirou'
            ], 401);
        }

        // Adicionar o usuário autenticado à requisição
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (!$header) {
            return null;
        }

        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }
}
