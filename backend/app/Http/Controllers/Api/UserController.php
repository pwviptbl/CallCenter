<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /** GET /api/v1/users */
    public function index(Request $request)
    {
        $query = User::with('company:id,name');

        // Busca por nome ou email
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->where('role', $request->get('role'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->get('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->get('company_id'));
        }

        $users = $query->orderBy('name')
            ->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    /** POST /api/v1/users */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:6',
            'role'       => ['required', Rule::in(['admin', 'attendant'])],
            'company_id' => 'nullable|exists:companies,id',
            'is_active'  => 'boolean',
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $data['is_active'] ?? true;

        $user = User::create($data);

        return response()->json($user->load('company:id,name'), 201);
    }

    /** GET /api/v1/users/{id} */
    public function show(int $id)
    {
        $user = User::with('company:id,name')->findOrFail($id);
        return response()->json($user);
    }

    /** PUT /api/v1/users/{id} */
    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name'       => 'sometimes|string|max:255',
            'email'      => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password'   => 'sometimes|string|min:6',
            'role'       => ['sometimes', Rule::in(['admin', 'attendant'])],
            'company_id' => 'nullable|exists:companies,id',
            'is_active'  => 'sometimes|boolean',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json($user->load('company:id,name'));
    }

    /** DELETE /api/v1/users/{id} */
    public function destroy(int $id, Request $request)
    {
        $user = User::findOrFail($id);

        // Não permite auto-exclusão
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Você não pode excluir seu próprio usuário.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Usuário excluído com sucesso.']);
    }

    /** POST /api/v1/users/{id}/toggle-active */
    public function toggleActive(int $id, Request $request)
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Você não pode desativar sua própria conta.'], 422);
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'ativado' : 'desativado';

        return response()->json([
            'message'   => "Usuário {$status} com sucesso.",
            'is_active' => $user->is_active,
        ]);
    }

    /** POST /api/v1/users/{id}/set-role */
    public function setRole(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'role' => ['required', Rule::in(['admin', 'attendant'])],
        ]);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Você não pode alterar sua própria função.'], 422);
        }

        $user->update(['role' => $request->role]);

        $roleLabel = $user->role === 'admin' ? 'Administrador' : 'Atendente';

        return response()->json([
            'message' => "Função alterada para {$roleLabel} com sucesso.",
            'role'    => $user->role,
        ]);
    }
}
