<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Jobs\DeploySiteJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    /**
     * Handle GitHub webhook for auto-deployment.
     */
    public function deploy(Request $request, string $token)
    {
        $site = Site::where('webhook_token', $token)
            ->where('auto_deploy', true)
            ->first();

        if (!$site) {
            return response()->json(['error' => 'Invalid webhook token'], 404);
        }

        // Verify webhook is from GitHub (optional but recommended)
        $payload = $request->all();
        $event = $request->header('X-GitHub-Event');

        // Only deploy on push events to the configured branch
        if ($event === 'push') {
            $branch = $payload['ref'] ?? '';
            $branchName = str_replace('refs/heads/', '', $branch);

            if ($branchName === ($site->repository_branch ?? 'main')) {
                // Generate unique job ID
                $jobId = Str::uuid()->toString();

                // Dispatch deployment job
                DeploySiteJob::dispatch($site, $branchName, $jobId);

                Log::info('Auto-deployment triggered via webhook', [
                    'site_id' => $site->id,
                    'domain' => $site->domain,
                    'branch' => $branchName,
                    'commit' => $payload['head_commit']['id'] ?? null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Deployment queued',
                    'site' => $site->domain,
                ]);
            }
        }

        return response()->json(['message' => 'Webhook received but no deployment triggered']);
    }
}
