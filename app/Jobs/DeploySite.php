<?php

namespace App\Jobs;

use App\Models\Deployment;
use App\Services\DeploymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeploySite implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Deployment $deployment
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DeploymentService $deploymentService): void
    {
        try {
            $deploymentService->executeDeployment($this->deployment);
        } catch (\Exception $e) {
            Log::error('Deployment failed', [
                'deployment_id' => $this->deployment->id,
                'error' => $e->getMessage(),
            ]);

            $this->deployment->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->deployment->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'completed_at' => now(),
        ]);
    }
}
