<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\DeploymentController;
use App\Http\Controllers\Api\ServerController;
use App\Http\Controllers\Api\SiteController;
use Illuminate\Support\Facades\Route;

// Agent endpoints (no auth required, uses token)
Route::prefix('agent')->group(function () {
    Route::post('/register', [AgentController::class, 'register']);
    Route::post('/metrics', [AgentController::class, 'reportMetrics']);
    Route::get('/commands', [AgentController::class, 'getCommands']);
});

// Authenticated API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('servers', ServerController::class)->names([
        'index' => 'api.servers.index',
        'store' => 'api.servers.store',
        'show' => 'api.servers.show',
        'update' => 'api.servers.update',
        'destroy' => 'api.servers.destroy',
    ]);
    Route::post('servers/{server}/test-connection', [ServerController::class, 'testConnection'])->name('api.servers.test-connection');
    
    Route::apiResource('sites', SiteController::class)->names([
        'index' => 'api.sites.index',
        'store' => 'api.sites.store',
        'show' => 'api.sites.show',
        'update' => 'api.sites.update',
        'destroy' => 'api.sites.destroy',
    ]);
    Route::apiResource('deployments', DeploymentController::class)->names([
        'index' => 'api.deployments.index',
        'store' => 'api.deployments.store',
        'show' => 'api.deployments.show',
        'update' => 'api.deployments.update',
        'destroy' => 'api.deployments.destroy',
    ]);
});

