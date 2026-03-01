<?php

namespace App\Events;

use App\Models\ServiceRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceRequestUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ServiceRequest $serviceRequest,
        public readonly string $action = 'updated',
    ) {}

    /**
     * Canal público por empresa — o frontend escuta "company.{id}".
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("company.{$this->serviceRequest->company_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return "service-request.{$this->action}";
    }

    public function broadcastWith(): array
    {
        return [
            'id'            => $this->serviceRequest->id,
            'status'        => $this->serviceRequest->status,
            'urgency_level' => $this->serviceRequest->urgency_level,
            'contact_name'  => $this->serviceRequest->contact_name,
            'contact_phone' => $this->serviceRequest->contact_phone,
            'attendant_id'  => $this->serviceRequest->attendant_id,
            'updated_at'    => $this->serviceRequest->updated_at?->toIso8601String(),
            'action'        => $this->action,
        ];
    }
}
