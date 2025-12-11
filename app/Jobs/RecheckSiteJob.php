<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\SiteScannerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RecheckSiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Site $site,
        public string $jobId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SiteScannerService $scanner): void
    {
        try {
            // Update job status to processing
            Cache::put("recheck_site_job_{$this->jobId}", [
                'status' => 'processing',
                'progress' => 0,
                'message' => 'Re-checking site repository and framework...',
            ], now()->addMinutes(10));

            // Re-check site
            $detected = $scanner->recheckSite($this->site);

            // Store results in cache
            Cache::put("recheck_site_job_{$this->jobId}", [
                'status' => 'completed',
                'progress' => 100,
                'message' => 'Site re-check completed successfully',
                'detected' => $detected,
            ], now()->addHours(1));

        } catch (\Exception $e) {
            Log::error('RecheckSiteJob failed', [
                'site_id' => $this->site->id,
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Store error in cache
            Cache::put("recheck_site_job_{$this->jobId}", [
                'status' => 'failed',
                'progress' => 0,
                'message' => 'Re-check failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], now()->addHours(1));
        }
    }
}
