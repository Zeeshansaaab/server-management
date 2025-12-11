<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\DeploymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DeploySiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Site $site,
        public ?string $branch = null,
        public string $jobId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DeploymentService $deploymentService): void
    {
        try {
            // Update job status to processing
            Cache::put("deploy_site_job_{$this->jobId}", [
                'status' => 'processing',
                'progress' => 0,
                'message' => 'Starting deployment...',
            ], now()->addMinutes(30));

            // Deploy site
            $deployment = $deploymentService->deploy($this->site, $this->branch);

            // Store results in cache
            Cache::put("deploy_site_job_{$this->jobId}", [
                'status' => $deployment->status === 'success' ? 'completed' : 'failed',
                'progress' => 100,
                'message' => $deployment->status === 'success' ? 'Deployment completed successfully' : 'Deployment failed',
                'deployment' => $deployment->toArray(),
            ], now()->addHours(1));

        } catch (\Exception $e) {
            Log::error('DeploySiteJob failed', [
                'site_id' => $this->site->id,
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Store error in cache
            Cache::put("deploy_site_job_{$this->jobId}", [
                'status' => 'failed',
                'progress' => 0,
                'message' => 'Deployment failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], now()->addHours(1));
        }
    }
}

