<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Central\AuthController as CentralAuthController;
use App\Http\Controllers\Central\WalletController;
use App\Http\Controllers\Tenant\AuthController as TenantAuthController;
use App\Http\Controllers\Tenant\ProjectController;
use App\Http\Controllers\Tenant\InvestmentController;

/*
|--------------------------------------------------------------------------
| Central API Routes (Root Domain)
|--------------------------------------------------------------------------
| Handled by default domain (e.g., localhost or domain.com)
*/
Route::domain('localhost')->group(function () {
    
    // Auth for Investors & Agents
    Route::post('/login', [CentralAuthController::class, 'login']);
    
    // Authenticated Central Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/wallet/topup', [WalletController::class, 'topup']);
    });

    // Internal System Routes
    Route::middleware(\App\Http\Middleware\RequireSystemApiKey::class)->group(function () {
        Route::post('/system/exchange-rates', [\App\Http\Controllers\Central\ExchangeRateController::class, 'sync']);
    });
});

/*
|--------------------------------------------------------------------------
| Tenant API Routes (Subdomains)
|--------------------------------------------------------------------------
| Handled by {tenant}.localhost. Enforced by TenantMiddleware.
*/
Route::domain('{tenant}.localhost')->middleware(\App\Http\Middleware\TenantMiddleware::class)->group(function () {
    
    // Auth for Tenant Admins & Issuers (Tenant DB)
    Route::post('/tenant/login', [TenantAuthController::class, 'login']);

    // Authenticated Tenant Routes
    Route::middleware('auth:sanctum')->group(function () {
        
        // Admin & Issuer Routes
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::post('/projects/{project}/publish', [ProjectController::class, 'publish']);
        
        // Investor Routes (Investors access tenant routes to invest)
        Route::post('/projects/{project}/invest', [InvestmentController::class, 'invest'])->name('tenant.invest');
    });
});
