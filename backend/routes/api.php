<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\UrgencyKeywordController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WhatsappInstanceController;
use App\Http\Controllers\Api\WhatsappWebhookController;

// ─── Webhook Evolution API (público) ─────────────────────────────────────────
Route::post('webhook/whatsapp', [WhatsappWebhookController::class, 'handle']);

// ─── Autenticação (pública) ───────────────────────────────────────────────────
Route::prefix('v1/auth')->group(function () {
    Route::post('login',  [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('me',      [AuthController::class, 'me'])->middleware('auth:sanctum');
});

// ─── Rotas protegidas (requer login + conta ativa) ────────────────────────────
Route::prefix('v1')->middleware(['auth:sanctum', 'user.active'])->group(function () {

    // ── Urgência: endpoint público para atendentes e admins ──────────────────
    Route::post('urgency-keywords/test',    [UrgencyKeywordController::class, 'test']);
    Route::post('urgency-keywords/analyze', [UrgencyKeywordController::class, 'analyze']);

    // ── Somente ADMIN ────────────────────────────────────────────────────────
    Route::middleware('role.admin')->group(function () {

        // Gestão de empresas
        Route::apiResource('companies', CompanyController::class);
        Route::post('companies/{id}/restore', [CompanyController::class, 'restore']);

        // Gestão de keywords de urgência
        Route::apiResource('urgency-keywords', UrgencyKeywordController::class);
        Route::post('urgency-keywords/{id}/restore', [UrgencyKeywordController::class, 'restore']);

        // Gestão de usuários
        Route::apiResource('users', UserController::class);
        Route::post('users/{id}/toggle-active', [UserController::class, 'toggleActive']);
        Route::post('users/{id}/set-role',       [UserController::class, 'setRole']);

        // Gestão de instâncias WhatsApp (somente admin)
        Route::apiResource('whatsapp-instances', WhatsappInstanceController::class);
        Route::get('whatsapp-instances/{whatsappInstance}/status',
            [WhatsappInstanceController::class, 'status']);
        Route::patch('whatsapp-instances/{whatsappInstance}/status',
            [WhatsappInstanceController::class, 'updateStatus']);
    });

    // ── Solicitações de atendimento (admin + atendente) ───────────────────────
    Route::get('service-requests/stats', [ServiceRequestController::class, 'stats']);
    Route::apiResource('service-requests', ServiceRequestController::class)
        ->only(['index', 'show', 'store']);
    Route::post('service-requests/{serviceRequest}/assign',
        [ServiceRequestController::class, 'assign']);
    Route::patch('service-requests/{serviceRequest}/status',
        [ServiceRequestController::class, 'updateStatus']);

    // ── Mensagens de uma solicitação ──────────────────────────────────────────
    Route::get('service-requests/{serviceRequest}/messages',
        [MessageController::class, 'index']);
    Route::post('service-requests/{serviceRequest}/messages',
        [MessageController::class, 'store']);
});

