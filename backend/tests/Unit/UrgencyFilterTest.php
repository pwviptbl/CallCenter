<?php

use App\Models\Company;
use App\Models\ServiceRequest;
use App\Models\UrgencyKeyword;
use App\Services\UrgencyFilter;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

// ── analyze() ─────────────────────────────────────────────────────────────

test('retorna não urgente quando não há keywords cadastradas', function () {
    $filter = app(UrgencyFilter::class);

    $result = $filter->analyze('Olá, preciso de ajuda com o elevador.');

    expect($result['is_urgent'])->toBeFalse();
    expect($result['matched_keywords'])->toBeEmpty();
    expect($result['priority_level'])->toBe(0);
});

test('detecta keyword exata e retorna prioridade correta', function () {
    UrgencyKeyword::factory()->create([
        'keyword'        => 'preso',
        'match_type'     => 'contains',
        'priority_level' => 10,
        'active'         => true,
        'company_id'     => null,
    ]);

    $filter = app(UrgencyFilter::class);
    $result = $filter->analyze('Socorro, estou preso no elevador!');

    expect($result['is_urgent'])->toBeTrue();
    expect($result['priority_level'])->toBe(10);
    expect($result['matched_keywords'])->toHaveCount(1);
    expect($result['matched_keywords'][0]['keyword'])->toBe('preso');
});

test('retorna a maior prioridade quando há múltiplas keywords', function () {
    UrgencyKeyword::factory()->create([
        'keyword' => 'preso', 'match_type' => 'contains', 'priority_level' => 10, 'active' => true, 'company_id' => null,
    ]);
    UrgencyKeyword::factory()->create([
        'keyword' => 'lento', 'match_type' => 'contains', 'priority_level' => 3, 'active' => true, 'company_id' => null,
    ]);

    $filter = app(UrgencyFilter::class);
    $result = $filter->analyze('O elevador está lento e eu fiquei preso!');

    expect($result['priority_level'])->toBe(10);
    expect($result['matched_keywords'])->toHaveCount(2);
});

test('keyword inativa não é detectada', function () {
    UrgencyKeyword::factory()->create([
        'keyword' => 'fogo', 'match_type' => 'contains', 'priority_level' => 10, 'active' => false, 'company_id' => null,
    ]);

    $filter = app(UrgencyFilter::class);
    $result = $filter->analyze('Tem fogo no andar!');

    expect($result['is_urgent'])->toBeFalse();
});

test('keyword com case insensitive funciona por padrão', function () {
    UrgencyKeyword::factory()->create([
        'keyword' => 'SOCORRO', 'match_type' => 'contains', 'priority_level' => 9,
        'active' => true, 'case_sensitive' => false, 'company_id' => null,
    ]);

    $filter = app(UrgencyFilter::class);
    $result = $filter->analyze('socorro, alguém me ajude!');

    expect($result['is_urgent'])->toBeTrue();
});

test('keyword de empresa específica não detecta mensagem de outra empresa', function () {
    $company = Company::factory()->create();

    UrgencyKeyword::factory()->create([
        'keyword'        => 'fumaca',
        'match_type'     => 'contains',
        'priority_level' => 8,
        'active'         => true,
        'company_id'     => null,
    ]);
    UrgencyKeyword::factory()->create([
        'keyword'        => 'queimando',
        'match_type'     => 'contains',
        'priority_level' => 9,
        'active'         => true,
        'company_id'     => $company->id,
    ]);
    $other = Company::factory()->create();

    $filter   = app(UrgencyFilter::class);

    // Outra empresa não deve detectar keyword dessa empresa
    $result = $filter->analyze('O painel está queimando!', $other->id);

    expect($result['is_urgent'])->toBeFalse();
});

test('keyword global é detectada em qualquer empresa', function () {
    UrgencyKeyword::factory()->create([
        'keyword'        => 'fumaca',
        'match_type'     => 'contains',
        'priority_level' => 8,
        'active'         => true,
        'company_id'     => null,
    ]);

    $company = Company::factory()->create();
    $filter  = app(UrgencyFilter::class);

    $result = $filter->analyze('Tem muita fumaca aqui!', $company->id);

    expect($result['is_urgent'])->toBeTrue();
});

// ── testKeyword() ──────────────────────────────────────────────────────────

test('testKeyword com match_type contains funciona corretamente', function () {
    $filter = app(UrgencyFilter::class);

    expect($filter->testKeyword('preso', 'contains', 'estou preso no elevador'))->toBeTrue();
    expect($filter->testKeyword('preso', 'contains', 'elevador parado'))->toBeFalse();
});

test('testKeyword com match_type regex funciona corretamente', function () {
    $filter = app(UrgencyFilter::class);

    expect($filter->testKeyword('\bfogo\b', 'regex', 'tem fogo no andar'))->toBeTrue();
    expect($filter->testKeyword('\bfogo\b', 'regex', 'fotografei o elevador'))->toBeFalse();
});

test('testKeyword com whole_word ignora ocorrência dentro de outra palavra', function () {
    $filter = app(UrgencyFilter::class);

    expect($filter->testKeyword('fogo', 'contains', 'o fogo apagou', false, true))->toBeTrue();
    expect($filter->testKeyword('fogo', 'contains', 'fotografei', false, true))->toBeFalse();
});
