<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompanyController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rotas públicas ou protegidas por Sanctum
Route::prefix('v1')->group(function () {
    // Rotas de empresas
    Route::apiResource('companies', CompanyController::class);
    Route::post('companies/{id}/restore', [CompanyController::class, 'restore'])->name('companies.restore');
});
