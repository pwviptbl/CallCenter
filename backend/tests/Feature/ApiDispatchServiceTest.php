<?php

use App\Jobs\DispatchToCompanyApiJob;
use App\Models\Company;
use App\Models\ServiceRequest;
use App\Services\ApiDispatchService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

// ── ApiDispatchService ─────────────────────────────────────────────────────

test('retorna erro quando empresa não tem api_enabled', function () {
    $company = Company::factory()->create(['api_enabled' => false, 'api_endpoint' => null]);
    $sr      = ServiceRequest::factory()->create(['company_id' => $company->id]);

    $service = app(ApiDispatchService::class);
    $result  = $service->dispatch($sr);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('sem integração');
});

test('envia POST para o endpoint da empresa com payload correto', function () {
    Http::fake([
        'https://erp.example.com/chamado' => Http::response(['id' => 'TKT-001'], 200),
    ]);

    $company = Company::factory()->create([
        'api_enabled'  => true,
        'api_endpoint' => 'https://erp.example.com/chamado',
        'api_method'   => 'POST',
        'api_key'      => 'minha-api-key',
    ]);

    $sr = ServiceRequest::factory()->create([
        'company_id'    => $company->id,
        'contact_name'  => 'João Silva',
        'contact_phone' => '+5511999990001',
        'collected_data'=> ['nome' => 'João', 'problema' => 'Elevador parado'],
    ]);

    $service = app(ApiDispatchService::class);
    $result  = $service->dispatch($sr);

    expect($result['success'])->toBeTrue();
    expect($result['status_code'])->toBe(200);
    expect($result['response'])->toHaveKey('id', 'TKT-001');

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://erp.example.com/chamado'
            && $request->method() === 'POST'
            && isset($request['chamado']['contato_nome'])
            && $request->hasHeader('Authorization', 'Bearer minha-api-key');
    });
});

test('retorna erro quando endpoint retorna status 4xx', function () {
    Http::fake([
        'https://erp.example.com/*' => Http::response(['error' => 'Not found'], 404),
    ]);

    $company = Company::factory()->create([
        'api_enabled'  => true,
        'api_endpoint' => 'https://erp.example.com/chamado',
        'api_method'   => 'POST',
    ]);
    $sr = ServiceRequest::factory()->create(['company_id' => $company->id]);

    $result = app(ApiDispatchService::class)->dispatch($sr);

    expect($result['success'])->toBeFalse();
    expect($result['status_code'])->toBe(404);
    expect($result['error'])->toContain('404');
});

test('lida com timeout de conexão graciosamente', function () {
    Http::fake([
        '*' => function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
        },
    ]);

    $company = Company::factory()->create([
        'api_enabled'  => true,
        'api_endpoint' => 'https://erp-timeout.example.com/chamado',
        'api_method'   => 'POST',
    ]);
    $sr = ServiceRequest::factory()->create(['company_id' => $company->id]);

    $result = app(ApiDispatchService::class)->dispatch($sr);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('timeout');
});

// ── DispatchToCompanyApiJob ────────────────────────────────────────────────

test('job resolve SR quando API retorna sucesso', function () {
    Http::fake([
        'https://erp.example.com/chamado' => Http::response(['id' => 'TKT-999'], 200),
    ]);

    $company = Company::factory()->create([
        'api_enabled'  => true,
        'api_endpoint' => 'https://erp.example.com/chamado',
        'api_method'   => 'POST',
    ]);

    $sr = ServiceRequest::factory()->create([
        'company_id' => $company->id,
        'status'     => ServiceRequest::STATUS_SENT_API,
    ]);

    (new DispatchToCompanyApiJob($sr->id))->handle(app(ApiDispatchService::class));

    expect($sr->fresh()->status)->toBe(ServiceRequest::STATUS_RESOLVED);
    expect($sr->fresh()->external_ticket_id)->toBe('TKT-999');
    expect($sr->fresh()->api_sent_at)->not->toBeNull();
});

test('job marca SR como failed após esgotar tentativas', function () {
    Http::fake([
        '*' => Http::response(['error' => 'Internal Server Error'], 500),
    ]);

    $company = Company::factory()->create([
        'api_enabled'  => true,
        'api_endpoint' => 'https://erp.example.com/chamado',
        'api_method'   => 'POST',
    ]);

    $sr = ServiceRequest::factory()->create([
        'company_id' => $company->id,
        'status'     => ServiceRequest::STATUS_SENT_API,
    ]);

    // Subclasse que simula o último attempt
    $job = new class ($sr->id) extends DispatchToCompanyApiJob {
        public function attempts(): int { return $this->tries; }
    };

    $job->handle(app(ApiDispatchService::class));

    expect($sr->fresh()->status)->toBe(ServiceRequest::STATUS_FAILED);
});
