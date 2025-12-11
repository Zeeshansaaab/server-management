<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Server routes
    Route::get('/servers', [DashboardController::class, 'servers'])->name('servers.index');
    Route::post('/servers', [DashboardController::class, 'storeServer'])->name('servers.store');
    Route::post('/servers/test-connection', [DashboardController::class, 'testServerConnection'])->name('servers.test-connection');
    Route::get('/servers/{server}', [DashboardController::class, 'server'])->name('servers.show');
    Route::post('/servers/{server}/check-status', [DashboardController::class, 'checkServerStatus'])->name('servers.check-status');
    Route::post('/servers/{server}/scan-sites', [DashboardController::class, 'scanSites'])->name('servers.scan-sites');
    Route::get('/servers/{server}/scan-sites-status', [DashboardController::class, 'checkScanSitesStatus'])->name('servers.scan-sites-status');
    Route::post('/servers/{server}/import-sites', [DashboardController::class, 'importSites'])->name('servers.import-sites');
    
    // Site routes
    Route::get('/sites', [DashboardController::class, 'sites'])->name('sites.index');
    Route::post('/sites', [DashboardController::class, 'storeSite'])->name('sites.store');
    Route::get('/sites/{site}', [DashboardController::class, 'site'])->name('sites.show');
    Route::post('/sites/{site}', [DashboardController::class, 'updateSite'])->name('sites.update');
    Route::post('/sites/{site}/recheck', [DashboardController::class, 'recheckSite'])->name('sites.recheck');
    Route::get('/sites/{site}/recheck-status', [DashboardController::class, 'checkRecheckStatus'])->name('sites.recheck-status');
    Route::post('/sites/{site}/deploy', [DashboardController::class, 'deploySite'])->name('sites.deploy');
    Route::get('/sites/{site}/deployment-status', [DashboardController::class, 'checkDeploymentStatus'])->name('sites.deployment-status');
    Route::get('/sites/{site}/deployment-script', [DashboardController::class, 'getDeploymentScript'])->name('sites.deployment-script');
    Route::post('/sites/{site}/check-health', [DashboardController::class, 'checkSiteHealth'])->name('sites.check-health');
    Route::post('/sites/{site}/ssl/request', [DashboardController::class, 'requestSslCertificate'])->name('sites.ssl.request');
    Route::post('/sites/{site}/ssl/renew', [DashboardController::class, 'renewSslCertificate'])->name('sites.ssl.renew');
    
    // Cron job routes
    Route::get('/sites/{site}/cron-jobs', [DashboardController::class, 'listCronJobs'])->name('sites.cron-jobs');
    Route::post('/sites/{site}/cron-jobs', [DashboardController::class, 'storeCronJob'])->name('sites.cron-jobs.store');
    Route::delete('/cron-jobs/{scheduledCommand}', [DashboardController::class, 'deleteCronJob'])->name('cron-jobs.delete');
    
    // PM2 logs and .env file routes
    Route::get('/sites/{site}/pm2-logs', [DashboardController::class, 'getPm2Logs'])->name('sites.pm2-logs');
    Route::get('/sites/{site}/env-file', [DashboardController::class, 'getEnvFile'])->name('sites.env-file');
    Route::post('/sites/{site}/env-file', [DashboardController::class, 'updateEnvFile'])->name('sites.env-file.update');
});

// Webhook routes (no auth required - uses token authentication)
Route::post('/api/webhooks/deploy/{token}', [\App\Http\Controllers\WebhookController::class, 'deploy'])->name('webhooks.deploy');

require __DIR__.'/auth.php';
