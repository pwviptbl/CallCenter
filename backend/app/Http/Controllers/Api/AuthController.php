<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** POST /api/v1/auth/login */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Sua conta está desativada. Contate o administrador.',
            ], 403);
        }

        // Atualizar último login
        $user->update(['last_login_at' => now()]);

        // Revogar tokens antigos e gerar novo
        $user->tokens()->delete();
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user'  => $this->userResource($user),
            'token' => $token,
        ]);
    }

    /** GET /api/v1/auth/me */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $this->userResource($request->user()),
        ]);
    }

    /** POST /api/v1/auth/logout */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function userResource(User $user): array
    {
        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'role'         => $user->role,
            'is_active'    => $user->is_active,
            'company_id'   => $user->company_id,
            'last_login_at'=> $user->last_login_at?->toIso8601String(),
            'created_at'   => $user->created_at?->toIso8601String(),
        ];
    }
}
