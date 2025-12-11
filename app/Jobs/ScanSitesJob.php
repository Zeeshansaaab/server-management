<?php

namespace App\Jobs;

use App\Models\Server;
use App\Services\SiteScannerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ScanSitesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Server $server,
        public string $jobId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SiteScannerService $scanner): void
    {
        try {
            // Update job status to processing
            Cache::put("scan_sites_job_{$this->jobId}", [
                'status' => 'processing',
                'progress' => 0,
                'message' => 'Starting site scan...',
            ], now()->addMinutes(10));

            // Scan for sites
            $detectedSites = $scanner->scanForSites($this->server);

            // Store results in cache
            Cache::put("scan_sites_job_{$this->jobId}", [
                'status' => 'completed',
                'progress' => 100,
                'message' => 'Scan completed successfully',
                'sites' => $detectedSites,
                'count' => count($detectedSites),
            ], now()->addHours(1)); // Keep results for 1 hour

        } catch (\Exception $e) {
            Log::error('ScanSitesJob failed', [
                'server_id' => $this->server->id,
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Store error in cache
            Cache::put("scan_sites_job_{$this->jobId}", [
                'status' => 'failed',
                'progress' => 0,
                'message' => 'Scan failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], now()->addHours(1));
        }
    }
}

