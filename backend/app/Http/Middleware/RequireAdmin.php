<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        if (! $user->isAdmin()) {
            return response()->json(['message' => 'Acesso restrito a administradores.'], 403);
        }

        return $next($request);
    }
}
