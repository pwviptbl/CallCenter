<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\UrgencyKeywordController;
use App\Http\Controllers\Api\UserController;

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
    });
});

