<?php

use App\Jobs\ProcessAiMessageJob;
use App\Models\Company;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WhatsappInstance;
use Illuminate\Support\Facades\Queue;

// ── Helpers ────────────────────────────────────────────────────────────────

function makeWebhookPayload(string $phone = '5511999990001', string $message = 'Preciso de ajuda', string $instance = 'inst-01'): array
{
    return [
        'event'    => 'messages.upsert',
        'instance' => $instance,
        'data'     => [
            'key' => [
                'id'        => 'FAKEID123',
                'fromMe'    => false,
                'remoteJid' => "{$phone}@s.whatsapp.net",
            ],
            'pushName' => 'Morador Teste',
            'message'  => ['conversation' => $message],
        ],
    ];
}

// ── Autenticação do webhook ────────────────────────────────────────────────

test('rejeita request com secret inválido', function () {
    config(['services.evolution.secret' => 'secret-correto']);

    $this->postJson('/api/webhook/whatsapp', makeWebhookPayload(), [
        'apikey' => 'secret-errado',
    ])->assertStatus(401);
});

test('aceita request sem secret quando não configurado', function () {
    Queue::fake();
    config(['services.evolution.secret' => null]);

    $response = $this->postJson('/api/webhook/whatsapp', makeWebhookPayload());

    $response->assertStatus(200);
});

// ── Filtragem de eventos ───────────────────────────────────────────────────

test('ignora evento que não seja messages.upsert', function () {
    Queue::fake();

    $payload = makeWebhookPayload();
    $payload['event'] = 'connection.update';

    $this->postJson('/api/webhook/whatsapp', $payload)->assertJson(['ok' => true]);

    Queue::assertNothingPushed();
});

test('ignora mensagem fromMe=true', function () {
    Queue::fake();

    $payload = makeWebhookPayload();
    $payload['data']['key']['fromMe'] = true;

    $this->postJson('/api/webhook/whatsapp', $payload)->assertStatus(200);

    Queue::assertNothingPushed();
});

// ── Criação de ServiceRequest ──────────────────────────────────────────────

test('cria service request para novo contato', function () {
    Queue::fake();

    $company  = Company::factory()->create(['active' => true]);
    WhatsappInstance::factory()->create([
        'company_id'   => $company->id,
        'instance_key' => 'inst-01',
        'is_active'    => true,
    ]);

    $this->postJson('/api/webhook/whatsapp', makeWebhookPayload(
        phone: '5511999990001',
        instance: 'inst-01'
    ))->assertStatus(200);

    // SR criado com status pending; o job (enfileirado) que muda para ai_collecting
    $this->assertDatabaseHas('service_requests', [
        'contact_phone' => '+5511999990001',
        'status'        => 'pending',
    ]);

    Queue::assertPushed(ProcessAiMessageJob::class);
});

test('reutiliza service request aberto do mesmo contato', function () {
    Queue::fake();

    $company  = Company::factory()->create(['active' => true]);
    $instance = WhatsappInstance::factory()->create([
        'company_id'   => $company->id,
        'instance_key' => 'inst-01',
        'is_active'    => true,
    ]);

    $sr = ServiceRequest::factory()->create([
        'company_id'            => $company->id,
        'whatsapp_instance_id'  => $instance->id,
        'contact_phone'         => '+5511999990001',
        'status'                => 'pending',
    ]);

    $this->postJson('/api/webhook/whatsapp', makeWebhookPayload(
        phone: '5511999990001',
        instance: 'inst-01'
    ))->assertStatus(200);

    expect(ServiceRequest::count())->toBe(1);
});

// ── Despacho de job ────────────────────────────────────────────────────────

test('despacha ProcessAiMessageJob quando SR não tem atendente', function () {
    Queue::fake();

    $company  = Company::factory()->create(['active' => true]);
    WhatsappInstance::factory()->create([
        'company_id'   => $company->id,
        'instance_key' => 'inst-02',
        'is_active'    => true,
    ]);

    $this->postJson('/api/webhook/whatsapp', makeWebhookPayload(
        phone: '5511999990002',
        instance: 'inst-02'
    ))->assertStatus(200)->assertJson(['ok' => true]);

    Queue::assertPushed(ProcessAiMessageJob::class);
});

test('não despacha job quando SR já tem atendente (in_progress)', function () {
    Queue::fake();

    $company  = Company::factory()->create();
    $instance = WhatsappInstance::factory()->create([
        'company_id'   => $company->id,
        'instance_key' => 'inst-03',
        'is_active'    => true,
    ]);
    $attendant = User::factory()->create(['company_id' => $company->id]);

    ServiceRequest::factory()->create([
        'company_id'           => $company->id,
        'whatsapp_instance_id' => $instance->id,
        'contact_phone'        => '+5511999990003',
        'status'               => 'in_progress',
        'attendant_id'         => $attendant->id,
    ]);

    $this->postJson('/api/webhook/whatsapp', makeWebhookPayload(
        phone: '5511999990003',
        instance: 'inst-03'
    ));

    Queue::assertNotPushed(ProcessAiMessageJob::class);
});
