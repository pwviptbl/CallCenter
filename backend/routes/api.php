<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\UrgencyKeywordController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rotas públicas ou protegidas por Sanctum
Route::prefix('v1')->group(function () {
    // Rotas de empresas
    Route::apiResource('companies', CompanyController::class);
    Route::post('companies/{id}/restore', [CompanyController::class, 'restore'])->name('companies.restore');
    
    // Rotas de keywords de urgência
    Route::apiResource('urgency-keywords', UrgencyKeywordController::class);
    Route::post('urgency-keywords/{id}/restore', [UrgencyKeywordController::class, 'restore'])->name('urgency-keywords.restore');
    Route::post('urgency-keywords/test', [UrgencyKeywordController::class, 'test'])->name('urgency-keywords.test');
    Route::post('urgency-keywords/analyze', [UrgencyKeywordController::class, 'analyze'])->name('urgency-keywords.analyze');
});
