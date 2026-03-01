<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            // Revogar tokens de conta desativada
            $user->tokens()->delete();
            return response()->json(['message' => 'Sua conta está desativada.'], 403);
        }

        return $next($request);
    }
}
