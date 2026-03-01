<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Lista as mensagens de uma solicitação.
     */
    public function index(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorizeRequest($request, $serviceRequest);

        $messages = $serviceRequest->messages()
            ->with('sender:id,name,role')
            ->orderBy('created_at')
            ->get();

        // Marca como lidas as mensagens inbound não lidas
        $serviceRequest->messages()
            ->where('direction', Message::DIRECTION_INBOUND)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    /**
     * Envia (registra) uma nova mensagem do atendente.
     */
    public function store(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorizeRequest($request, $serviceRequest);

        if ($serviceRequest->isClosed()) {
            return response()->json(['message' => 'Solicitação encerrada.'], 422);
        }

        $data = $request->validate([
            'content'    => 'required_without:media_url|nullable|string',
            'media_url'  => 'required_without:content|nullable|url',
            'media_type' => 'nullable|in:image,audio,video,document',
        ]);

        $user    = $request->user();
        $message = $serviceRequest->messages()->create([
            ...$data,
            'direction'   => Message::DIRECTION_OUTBOUND,
            'sender_type' => $user->isAdmin()
                ? Message::SENDER_ATTENDANT
                : Message::SENDER_ATTENDANT,
            'sender_id'   => $user->id,
            'is_read'     => true,
        ]);

        return response()->json($message->load('sender:id,name,role'), 201);
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
