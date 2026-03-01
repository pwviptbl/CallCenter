<?php

namespace App\Http\Controllers\Api;

use App\Events\ServiceRequestUpdated;
use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    /**
     * Lista as solicitações com filtros opcionais.
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = ServiceRequest::where('company_id', $user->company_id)
            ->with(['attendant:id,name', 'whatsappInstance:id,name,status'])
            ->orderByRaw("CASE urgency_level
                WHEN 'critical' THEN 1
                WHEN 'urgent'   THEN 2
                ELSE 3 END")
            ->orderBy('created_at', 'desc');

        // Se for atendente, só vê as abertas + as atribuídas a ele
        if ($user->isAttendant()) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('attendant_id')
                  ->orWhere('attendant_id', $user->id);
            })->open();
        }

        // Filtros opcionais
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($urgency = $request->get('urgency_level')) {
            $query->where('urgency_level', $urgency);
        }

        if ($channel = $request->get('channel')) {
            $query->where('channel', $channel);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('contact_name',  'ilike', "%{$search}%")
                  ->orWhere('contact_phone', 'ilike', "%{$search}%");
            });
        }

        $perPage = min($request->integer('per_page', 20), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Exibe uma solicitação com todo o histórico de mensagens.
     */
    public function show(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorizeRequest($request, $serviceRequest);

        $serviceRequest->load([
            'messages' => fn($q) => $q->orderBy('created_at'),
            'messages.sender:id,name,role',
            'attendant:id,name',
            'whatsappInstance:id,name,status,phone_number',
        ]);

        return response()->json($serviceRequest);
    }

    /**
     * Cria uma solicitação manual.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'contact_name'    => 'required|string|max:255',
            'contact_phone'   => 'required|string|max:20',
            'contact_message' => 'required|string',
            'notes'           => 'nullable|string',
        ]);

        $serviceRequest = ServiceRequest::create([
            ...$data,
            'company_id'   => $request->user()->company_id,
            'channel'      => ServiceRequest::CHANNEL_MANUAL,
            'status'       => ServiceRequest::STATUS_PENDING,
            'urgency_level'=> ServiceRequest::URGENCY_NORMAL,
        ]);

        return response()->json($serviceRequest, 201);
    }

    /**
     * Atribui a solicitação ao atendente logado.
     */
    public function assign(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorizeRequest($request, $serviceRequest);

        if ($serviceRequest->isClosed()) {
            return response()->json(['message' => 'Solicitação encerrada.'], 422);
        }

        $serviceRequest->update([
            'attendant_id' => $request->user()->id,
            'status'       => ServiceRequest::STATUS_IN_PROGRESS,
            'attended_at'  => now(),
        ]);

        $updated = $serviceRequest->fresh(['attendant:id,name']);
        broadcast(new ServiceRequestUpdated($updated, 'updated'));
        return response()->json($updated);
    }

    /**
     * Atualiza o status de uma solicitação.
     */
    public function updateStatus(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorizeRequest($request, $serviceRequest);

        $data = $request->validate([
            'status' => 'required|in:pending,ai_collecting,awaiting_review,in_progress,confirmed_manual,sent_api,resolved,failed',
            'notes'  => 'nullable|string',
        ]);

        $extra = [];

        if ($data['status'] === ServiceRequest::STATUS_RESOLVED) {
            $extra['resolved_at'] = now();
        }

        $serviceRequest->update([...$data, ...$extra]);

        $updated = $serviceRequest->fresh();
        broadcast(new ServiceRequestUpdated($updated, 'updated'));
        return response()->json($updated);
    }

    /**
     * Retorna estatísticas resumidas para o dashboard.
     */
    public function stats(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $total    = ServiceRequest::where('company_id', $companyId)->count();
        $pending  = ServiceRequest::where('company_id', $companyId)->pending()->count();
        $urgent   = ServiceRequest::where('company_id', $companyId)->urgent()->open()->count();
        $resolved = ServiceRequest::where('company_id', $companyId)->byStatus(ServiceRequest::STATUS_RESOLVED)->count();

        return response()->json(compact('total', 'pending', 'urgent', 'resolved'));
    }

    // ── Privado ───────────────────────────────────────────────────────────────

    private function authorizeRequest(Request $request, ServiceRequest $sr): void
    {
        abort_if(
            $sr->company_id !== $request->user()->company_id,
            403,
            'Sem permissão para esta solicitação.'
        );
    }
}
