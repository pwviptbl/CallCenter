<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Message;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WhatsappInstance;
use Illuminate\Database\Seeder;

class ServiceRequestSeeder extends Seeder
{
    public function run(): void
    {
        $company   = Company::first();
        $attendant = User::where('role', 'attendant')->first();

        if (! $company) {
            $this->command->warn('Nenhuma empresa encontrada. Rode CompanySeeder antes.');
            return;
        }

        // ── Instância WhatsApp de demonstração ────────────────────────────────
        $instance = WhatsappInstance::firstOrCreate(
            ['instance_key' => 'demo-instance-01'],
            [
                'company_id'          => $company->id,
                'name'                => 'WhatsApp Principal',
                'status'              => WhatsappInstance::STATUS_CONNECTED,
                'phone_number'        => '+5511999990001',
                'evolution_api_url'   => 'http://evolution-api:8080',
                'evolution_api_token' => 'demo-token-replace-me',
                'is_active'           => true,
            ]
        );

        // ── Solicitações de demonstração ──────────────────────────────────────
        $samples = [
            [
                'contact_name'    => 'João Silva',
                'contact_phone'   => '+5511912345678',
                'contact_message' => 'Preciso de atendimento urgente! Meu sistema caiu.',
                'status'          => ServiceRequest::STATUS_PENDING,
                'urgency_level'   => ServiceRequest::URGENCY_CRITICAL,
                'urgency_keywords'=> ['urgente', 'sistema caiu'],
                'channel'         => ServiceRequest::CHANNEL_WHATSAPP,
            ],
            [
                'contact_name'    => 'Maria Oliveira',
                'contact_phone'   => '+5511987654321',
                'contact_message' => 'Gostaria de saber o status do meu pedido.',
                'status'          => ServiceRequest::STATUS_IN_PROGRESS,
                'urgency_level'   => ServiceRequest::URGENCY_NORMAL,
                'urgency_keywords'=> [],
                'channel'         => ServiceRequest::CHANNEL_WHATSAPP,
                'attendant_id'    => $attendant?->id,
                'attended_at'     => now()->subMinutes(15),
            ],
            [
                'contact_name'    => 'Carlos Souza',
                'contact_phone'   => '+5511911112222',
                'contact_message' => 'Preciso cancelar meu contrato com urgência.',
                'status'          => ServiceRequest::STATUS_AWAITING_REVIEW,
                'urgency_level'   => ServiceRequest::URGENCY_URGENT,
                'urgency_keywords'=> ['cancelar', 'urgência'],
                'channel'         => ServiceRequest::CHANNEL_WHATSAPP,
            ],
            [
                'contact_name'    => 'Ana Lima',
                'contact_phone'   => '+5511933334444',
                'contact_message' => 'Quero informações sobre planos disponíveis.',
                'status'          => ServiceRequest::STATUS_RESOLVED,
                'urgency_level'   => ServiceRequest::URGENCY_NORMAL,
                'urgency_keywords'=> [],
                'channel'         => ServiceRequest::CHANNEL_MANUAL,
                'attendant_id'    => $attendant?->id,
                'attended_at'     => now()->subHour(),
                'resolved_at'     => now()->subMinutes(30),
            ],
            [
                'contact_name'    => 'Pedro Mendes',
                'contact_phone'   => '+5511955556666',
                'contact_message' => 'Sistema fora do ar! Preciso de suporte imediato!',
                'status'          => ServiceRequest::STATUS_PENDING,
                'urgency_level'   => ServiceRequest::URGENCY_CRITICAL,
                'urgency_keywords'=> ['fora do ar', 'imediato'],
                'channel'         => ServiceRequest::CHANNEL_WHATSAPP,
            ],
        ];

        foreach ($samples as $sample) {
            $sr = ServiceRequest::create([
                ...$sample,
                'company_id'           => $company->id,
                'whatsapp_instance_id' => in_array($sample['channel'], [ServiceRequest::CHANNEL_WHATSAPP])
                    ? $instance->id
                    : null,
            ]);

            // Adiciona algumas mensagens de demonstração
            $this->seedMessages($sr);
        }

        $this->command->info('✅  ServiceRequestSeeder: ' . count($samples) . ' solicitações criadas.');
    }

    private function seedMessages(ServiceRequest $sr): void
    {
        // Mensagem inicial do contato
        $sr->messages()->create([
            'direction'   => Message::DIRECTION_INBOUND,
            'sender_type' => Message::SENDER_CONTACT,
            'content'     => $sr->contact_message,
            'is_read'     => $sr->status !== ServiceRequest::STATUS_PENDING,
        ]);

        // Se em andamento ou resolvida, adiciona resposta
        if (in_array($sr->status, [
            ServiceRequest::STATUS_IN_PROGRESS,
            ServiceRequest::STATUS_RESOLVED,
        ])) {
            $sr->messages()->create([
                'direction'   => Message::DIRECTION_OUTBOUND,
                'sender_type' => Message::SENDER_ATTENDANT,
                'sender_id'   => $sr->attendant_id,
                'content'     => 'Olá! Já estou verificando o seu caso. Aguarde um momento.',
                'is_read'     => true,
            ]);
        }

        if ($sr->status === ServiceRequest::STATUS_RESOLVED) {
            $sr->messages()->create([
                'direction'   => Message::DIRECTION_OUTBOUND,
                'sender_type' => Message::SENDER_ATTENDANT,
                'sender_id'   => $sr->attendant_id,
                'content'     => 'Problema resolvido! Qualquer dúvida estou à disposição.',
                'is_read'     => true,
            ]);
        }
    }
}
