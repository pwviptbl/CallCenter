<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappInstance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsappInstanceController extends Controller
{
    /**
     * Lista todas as instâncias da empresa.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WhatsappInstance::where('company_id', $request->user()->company_id)
            ->orderBy('name');

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return response()->json($query->get());
    }

    /**
     * Cria nova instância.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'instance_key'       => 'required|string|max:255|unique:whatsapp_instances,instance_key',
            'evolution_api_url'  => 'required|url',
            'evolution_api_token'=> 'required|string|max:500',
            'is_active'          => 'boolean',
        ]);

        $instance = WhatsappInstance::create([
            ...$data,
            'company_id' => $request->user()->company_id,
            'status'     => WhatsappInstance::STATUS_DISCONNECTED,
        ]);

        return response()->json($instance, 201);
    }

    /**
     * Exibe uma instância.
     */
    public function show(Request $request, WhatsappInstance $whatsappInstance): JsonResponse
    {
        $this->authorizeInstance($request, $whatsappInstance);

        return response()->json($whatsappInstance);
    }

    /**
     * Atualiza uma instância.
     */
    public function update(Request $request, WhatsappInstance $whatsappInstance): JsonResponse
    {
        $this->authorizeInstance($request, $whatsappInstance);

        $data = $request->validate([
            'name'                => 'sometimes|string|max:255',
            'evolution_api_url'   => 'sometimes|url',
            'evolution_api_token' => 'sometimes|string|max:500',
            'is_active'           => 'sometimes|boolean',
        ]);

        $whatsappInstance->update($data);

        return response()->json($whatsappInstance->fresh());
    }

    /**
     * Remove uma instância.
     */
    public function destroy(Request $request, WhatsappInstance $whatsappInstance): JsonResponse
    {
        $this->authorizeInstance($request, $whatsappInstance);

        if ($whatsappInstance->serviceRequests()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir instância com solicitações vinculadas.',
            ], 422);
        }

        $whatsappInstance->delete();

        return response()->json(null, 204);
    }

    /**
     * Retorna o status atual da instância.
     */
    public function status(Request $request, WhatsappInstance $whatsappInstance): JsonResponse
    {
        $this->authorizeInstance($request, $whatsappInstance);

        return response()->json([
            'id'           => $whatsappInstance->id,
            'status'       => $whatsappInstance->status,
            'phone_number' => $whatsappInstance->phone_number,
            'is_active'    => $whatsappInstance->is_active,
        ]);
    }

    /**
     * Atualiza o status manualmente (usado pelo webhook da Evolution API).
     */
    public function updateStatus(Request $request, WhatsappInstance $whatsappInstance): JsonResponse
    {
        $data = $request->validate([
            'status'       => 'required|in:disconnected,qr_required,connecting,connected',
            'phone_number' => 'nullable|string|max:20',
        ]);

        $whatsappInstance->update($data);

        return response()->json($whatsappInstance->fresh());
    }

    // ── Privado ───────────────────────────────────────────────────────────────

    private function authorizeInstance(Request $request, WhatsappInstance $instance): void
    {
        abort_if(
            $instance->company_id !== $request->user()->company_id,
            403,
            'Sem permissão para esta instância.'
        );
    }
}
