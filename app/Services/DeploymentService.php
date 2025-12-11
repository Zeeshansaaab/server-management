<?php

namespace App\Services;

use App\Models\Site;
use App\Models\Deployment;
use App\Models\DeploymentLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DeploymentService
{
    public function __construct(
        private SshService $sshService
    ) {}

    /**
     * Deploy a site.
     */
    public function deploy(Site $site, ?string $branch = null, ?string $commitHash = null): Deployment
    {
        $server = $site->server;
        $branch = $branch ?? $site->repository_branch ?? 'main';
        $webDirectory = $site->getWebDirectoryPath();

        // Create deployment record
        $deployment = Deployment::create([
            'site_id' => $site->id,
            'branch' => $branch,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            // Get deployment commands (custom script or default)
            $commands = $this->getDeploymentCommands($site, $webDirectory, $branch, $commitHash);

            // Execute commands step by step and log each
            $stepOrder = 0;
            $allOutput = [];
            $hasError = false;

            foreach ($commands as $step => $command) {
                $stepOrder++;
                
                // Log step start
                DeploymentLog::create([
                    'deployment_id' => $deployment->id,
                    'step' => $step,
                    'output' => "Executing: {$command}\n",
                    'level' => 'info',
                    'order' => $stepOrder,
                ]);

                // Execute command
                // Note: Commands that start with 'bash -c' are already wrapped, so we don't need sudo
                // For other commands, use sudo if needed
                $useSudo = !str_starts_with(trim($command), 'bash -c');
                $result = $this->sshService->execute($server, $command, $useSudo);
                
                // Log output
                $output = $result['output'] ?? '';
                $error = $result['error'] ?? '';
                $logLevel = $result['success'] ? 'info' : 'error';
                
                if (!$result['success']) {
                    $hasError = true;
                    $output = ($output ? $output . "\n" : '') . "ERROR: " . $error;
                }

                DeploymentLog::create([
                    'deployment_id' => $deployment->id,
                    'step' => $step,
                    'output' => $output,
                    'level' => $logLevel,
                    'order' => $stepOrder + 0.5, // Half step for output
                ]);

                $allOutput[] = $output;

                // Stop on error (unless command has || true)
                if (!$result['success'] && !str_contains($command, '|| true')) {
                    throw new \Exception("Deployment failed at step '{$step}': {$error}");
                }
            }

            $result = [
                'success' => !$hasError,
                'output' => implode("\n", $allOutput),
                'error' => $hasError ? 'One or more deployment steps failed' : null,
            ];

            if ($result['success']) {
                // Get commit hash if repository exists
                $deployedCommitHash = $commitHash;
                if ($site->repository_url && !$deployedCommitHash) {
                    // Get commit hash from deployed directory
                    $commitResult = $this->sshService->execute($server, 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && git rev-parse HEAD 2>/dev/null || echo ''"), false);
                    $deployedCommitHash = trim($commitResult['output'] ?? '');
                }

                $deployment->update([
                    'status' => 'success',
                    'completed_at' => now(),
                    'commit_hash' => $deployedCommitHash,
                ]);

                // Perform health check after successful deployment
                try {
                    $this->performHealthCheck($site);
                } catch (\Exception $e) {
                    // Log but don't fail deployment
                    Log::warning('Health check failed after deployment', [
                        'site_id' => $site->id,
                        'deployment_id' => $deployment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $deployment->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => $result['error'],
                ]);
            }

            return $deployment;
        } catch (\Exception $e) {
            Log::error('Deployment failed', [
                'site_id' => $site->id,
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);

            $deployment->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            return $deployment;
        }
    }

    /**
     * Get deployment commands (custom script or default).
     */
    public function getDeploymentCommands(Site $site, string $webDirectory, string $branch, ?string $commitHash = null): array
    {
        // If custom deployment script exists, use it
        if (!empty($site->deployment_script)) {
            $script = $site->deployment_script;
            
            // Replace placeholders (keeping backward compatibility)
            $script = str_replace(
                ['{release_path}', '{current_path}', '{releases_path}', '{branch}', '{domain}', '{web_directory}', '{commit_hash}'],
                [$webDirectory, $webDirectory, $webDirectory . '/releases', $branch, $site->domain, $webDirectory, $commitHash ?? ''],
                $script
            );
            
            // Parse script into commands (split by newlines, filter empty)
            $lines = explode("\n", $script);
            $commands = [];
            $stepIndex = 0;
            $pendingCd = null;
            
            foreach ($lines as $line) {
                $line = trim($line);
                // Skip empty lines and comments
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }
                
                // Check if this is a standalone cd command (not combined with &&)
                $isStandaloneCd = preg_match('/^cd\s+/', $line) && !str_contains($line, '&&') && !str_contains($line, ';');
                
                if ($isStandaloneCd) {
                    // Store the cd command to combine with next command
                    $pendingCd = $line;
                    continue;
                }
                
                // If we have a pending cd, combine it with current command
                if ($pendingCd !== null) {
                    $line = $pendingCd . ' && ' . $line;
                    $pendingCd = null;
                }
                
                // Wrap commands that contain cd in a shell context to handle sudo properly
                // This ensures cd works even when sudo is used (cd is a shell builtin)
                if (preg_match('/\bcd\s+/', $line)) {
                    $line = 'bash -c ' . escapeshellarg($line);
                }
                
                $stepIndex++;
                $stepName = "step_" . $stepIndex;
                $commands[$stepName] = $line;
            }
            
            // If there's a pending cd at the end, add it as a no-op (cd only)
            if ($pendingCd !== null) {
                $stepIndex++;
                $stepName = "step_" . $stepIndex;
                $commands[$stepName] = 'bash -c ' . escapeshellarg($pendingCd . ' && :');
            }
            
            return $commands;
        }

        // Default deployment commands
        $commands = [];

        // 1. Ensure web directory exists
        $commands['ensure_directory'] = "mkdir -p " . escapeshellarg($webDirectory);

        // 2. Clone or update repository (direct checkout approach)
        if ($site->repository_url) {
            if ($this->directoryExists($site->server, $webDirectory . '/.git')) {
                // Repository exists - fetch and checkout
                $commands['git_fetch'] = 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && git fetch origin");
                
                if ($commitHash) {
                    // Checkout specific commit
                    $commands['git_checkout'] = 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && git checkout {$commitHash}");
                } else {
                    // Checkout latest from branch
                    $commands['git_checkout'] = 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && git reset --hard origin/{$branch}");
                }
            } else {
                // Clone new repository
                $commands['git_clone'] = "git clone -b {$branch} " . escapeshellarg($site->repository_url) . " " . escapeshellarg($webDirectory);
                
                // If specific commit requested, checkout to it
                if ($commitHash) {
                    $commands['git_checkout_commit'] = 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && git checkout {$commitHash}");
                }
            }
        } else {
            // No repository, just ensure directory exists
            $commands['create_directory'] = "mkdir -p " . escapeshellarg($webDirectory);
        }

        // Determine framework
        $isLaravel = $site->framework === 'laravel' || (!$site->framework && !$site->node_version);
        $isNextJs = $site->framework === 'nextjs' || $site->framework === 'react' || $site->node_version;

        // 3. Install dependencies based on framework
        if ($isLaravel) {
            // Laravel: Install composer dependencies - wrap in bash -c since it contains cd
            $commands['composer_install'] = 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && [ -f composer.json ] && composer install --no-dev --optimize-autoloader --no-interaction || true");
        }
        
        if ($isNextJs || $this->fileExists($site->server, $webDirectory . '/package.json')) {
            // Node.js/Next.js: Install npm dependencies - wrap in bash -c since it contains cd
            $nodeVersion = $site->node_version ? "nvm use {$site->node_version} && " : "";
            $commands['npm_install'] = 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && [ -f package.json ] && ({$nodeVersion}npm ci || {$nodeVersion}npm install) || true");
        }

        // 4. Build assets based on framework
        if ($isNextJs) {
            // Next.js: Build production - wrap in bash -c since it contains cd
            $nodeVersion = $site->node_version ? "nvm use {$site->node_version} && " : "";
            $commands['npm_build'] = 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && [ -f package.json ] && [ -f next.config.js ] && {$nodeVersion}npm run build || true");
        } elseif ($isLaravel) {
            // Laravel: Build frontend assets if Mix/Vite exists - wrap in bash -c since it contains cd
            $nodeVersion = $site->node_version ? "nvm use {$site->node_version} && " : "";
            $commands['npm_build'] = 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && [ -f package.json ] && ({$nodeVersion}npm run build || {$nodeVersion}npm run production) || true");
        }

        // 5. Keep .env file (it should already be there, no need to copy)

        // 6. Framework-specific setup
        if ($isLaravel) {
            // Laravel: Cache config, routes, views - wrap in bash -c since it contains cd
            $commands['laravel_cache'] = 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && [ -f artisan ] && php artisan config:cache && php artisan route:cache && php artisan view:cache || true");
            // Run migrations - wrap in bash -c since it contains cd
            $commands['laravel_migrate'] = 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && [ -f artisan ] && php artisan migrate --force || true");
        }

        // 7. Run deployment hooks (if deploy script exists) - wrap in bash -c since it contains cd
        $commands['deploy_hook'] = 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && [ -f deploy.sh ] && bash deploy.sh || true");

        // 8. Restart services based on framework
        if ($isLaravel && $site->php_version) {
            // Restart PHP-FPM for Laravel
            $commands['restart_php'] = "sudo systemctl reload php{$site->php_version}-fpm 2>/dev/null || sudo service php{$site->php_version}-fpm reload 2>/dev/null || true";
        } elseif ($isNextJs) {
            // Restart Next.js process (PM2 or similar)
            $commands['restart_node'] = "pm2 restart {$site->domain} 2>/dev/null || pm2 reload {$site->domain} 2>/dev/null || true";
        }

        return $commands;
    }

    /**
     * Check if directory exists on server.
     */
    private function directoryExists($server, string $path): bool
    {
        $result = $this->sshService->execute($server, "[ -d " . escapeshellarg($path) . " ] && echo 'exists' || echo ''");
        return trim($result['output']) === 'exists';
    }

    /**
     * Check if file exists on server.
     */
    private function fileExists($server, string $path): bool
    {
        $result = $this->sshService->execute($server, "[ -f " . escapeshellarg($path) . " ] && echo 'exists' || echo ''");
        return trim($result['output']) === 'exists';
    }

    /**
     * Rollback to a previous successful deployment.
     */
    public function rollback(Site $site, ?int $deploymentId = null): Deployment
    {
        $server = $site->server;
        $webDirectory = $site->getWebDirectoryPath();

        // Find target deployment
        if ($deploymentId) {
            $targetDeployment = Deployment::where('site_id', $site->id)
                ->where('id', $deploymentId)
                ->where('status', 'success')
                ->first();
            
            if (!$targetDeployment) {
                throw new \Exception('Deployment not found or not successful');
            }
        } else {
            // Find previous successful deployment (skip current if it's the latest)
            $targetDeployment = Deployment::where('site_id', $site->id)
                ->where('status', 'success')
                ->whereNotNull('commit_hash')
                ->orderBy('completed_at', 'desc')
                ->skip(1) // Skip the latest (current) deployment
                ->first();
            
            if (!$targetDeployment) {
                throw new \Exception('No previous successful deployment found to rollback to');
            }
        }

        if (empty($targetDeployment->commit_hash)) {
            throw new \Exception('Target deployment does not have a commit hash');
        }

        // Create new deployment record for rollback
        $rollbackDeployment = Deployment::create([
            'site_id' => $site->id,
            'branch' => $targetDeployment->branch,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            // Build rollback commands
            $commands = [];
            
            // 1. Fetch latest changes
            $commands['git_fetch'] = 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && git fetch origin");
            
            // 2. Checkout to previous commit
            $commands['git_checkout'] = 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && git checkout {$targetDeployment->commit_hash}");
            
            // 3. Re-run framework-specific steps (optional - can be skipped for faster rollback)
            $isLaravel = $site->framework === 'laravel' || (!$site->framework && !$site->node_version);
            
            if ($isLaravel) {
                // Clear caches
                $commands['clear_cache'] = 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && [ -f artisan ] && php artisan config:clear && php artisan route:clear && php artisan view:clear || true");
                // Re-cache
                $commands['cache'] = 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && [ -f artisan ] && php artisan config:cache && php artisan route:cache && php artisan view:cache || true");
            }
            
            // 4. Restart services
            if ($isLaravel && $site->php_version) {
                $commands['restart_php'] = "sudo systemctl reload php{$site->php_version}-fpm 2>/dev/null || sudo service php{$site->php_version}-fpm reload 2>/dev/null || true";
            } elseif ($site->framework === 'nextjs' || $site->node_version) {
                $commands['restart_node'] = "pm2 restart {$site->domain} 2>/dev/null || pm2 reload {$site->domain} 2>/dev/null || true";
            }

            // Execute rollback commands
            $stepOrder = 0;
            $hasError = false;

            foreach ($commands as $step => $command) {
                $stepOrder++;
                
                DeploymentLog::create([
                    'deployment_id' => $rollbackDeployment->id,
                    'step' => $step,
                    'output' => "Executing: {$command}\n",
                    'level' => 'info',
                    'order' => $stepOrder,
                ]);

                $useSudo = !str_starts_with(trim($command), 'bash -c');
                $result = $this->sshService->execute($server, $command, $useSudo);
                
                $output = $result['output'] ?? '';
                $error = $result['error'] ?? '';
                $logLevel = $result['success'] ? 'info' : 'error';
                
                if (!$result['success']) {
                    $hasError = true;
                    $output = ($output ? $output . "\n" : '') . "ERROR: " . $error;
                }

                DeploymentLog::create([
                    'deployment_id' => $rollbackDeployment->id,
                    'step' => $step,
                    'output' => $output,
                    'level' => $logLevel,
                    'order' => $stepOrder + 0.5,
                ]);

                if (!$result['success'] && !str_contains($command, '|| true')) {
                    throw new \Exception("Rollback failed at step '{$step}': {$error}");
                }
            }

            // Verify commit hash after rollback
            $verifyResult = $this->sshService->execute($server, 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && git rev-parse HEAD 2>/dev/null || echo ''"), false);
            $actualCommitHash = trim($verifyResult['output'] ?? '');

            $rollbackDeployment->update([
                'status' => $hasError ? 'failed' : 'success',
                'completed_at' => now(),
                'commit_hash' => $actualCommitHash ?: $targetDeployment->commit_hash,
            ]);

            // Perform health check
            if (!$hasError) {
                try {
                    $this->performHealthCheck($site);
                } catch (\Exception $e) {
                    Log::warning('Health check failed after rollback', [
                        'site_id' => $site->id,
                        'deployment_id' => $rollbackDeployment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $rollbackDeployment;
        } catch (\Exception $e) {
            Log::error('Rollback failed', [
                'site_id' => $site->id,
                'deployment_id' => $rollbackDeployment->id,
                'error' => $e->getMessage(),
            ]);

            $rollbackDeployment->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Retry a failed deployment.
     */
    public function retry(Deployment $failedDeployment): Deployment
    {
        $site = $failedDeployment->site;
        $server = $site->server;
        $branch = $failedDeployment->branch ?? $site->repository_branch ?? 'main';
        $commitHash = $failedDeployment->commit_hash; // Retry same commit if available
        $webDirectory = $site->getWebDirectoryPath();

        // Create new deployment record
        $retryDeployment = Deployment::create([
            'site_id' => $site->id,
            'branch' => $branch,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            // Get deployment commands (same as normal deployment)
            $commands = $this->getDeploymentCommands($site, $webDirectory, $branch, $commitHash);

            // Execute commands step by step and log each
            $stepOrder = 0;
            $allOutput = [];
            $hasError = false;

            foreach ($commands as $step => $command) {
                $stepOrder++;
                
                DeploymentLog::create([
                    'deployment_id' => $retryDeployment->id,
                    'step' => $step,
                    'output' => "Executing: {$command}\n",
                    'level' => 'info',
                    'order' => $stepOrder,
                ]);

                $useSudo = !str_starts_with(trim($command), 'bash -c');
                $result = $this->sshService->execute($server, $command, $useSudo);
                
                $output = $result['output'] ?? '';
                $error = $result['error'] ?? '';
                $logLevel = $result['success'] ? 'info' : 'error';
                
                if (!$result['success']) {
                    $hasError = true;
                    $output = ($output ? $output . "\n" : '') . "ERROR: " . $error;
                }

                DeploymentLog::create([
                    'deployment_id' => $retryDeployment->id,
                    'step' => $step,
                    'output' => $output,
                    'level' => $logLevel,
                    'order' => $stepOrder + 0.5,
                ]);

                $allOutput[] = $output;

                if (!$result['success'] && !str_contains($command, '|| true')) {
                    throw new \Exception("Retry failed at step '{$step}': {$error}");
                }
            }

            $result = [
                'success' => !$hasError,
                'output' => implode("\n", $allOutput),
                'error' => $hasError ? 'One or more deployment steps failed' : null,
            ];

            if ($result['success']) {
                // Get commit hash
                $deployedCommitHash = $commitHash;
                if ($site->repository_url && !$deployedCommitHash) {
                    $commitResult = $this->sshService->execute($server, 'bash -c ' . escapeshellarg("cd " . escapeshellarg($webDirectory) . " && git rev-parse HEAD 2>/dev/null || echo ''"), false);
                    $deployedCommitHash = trim($commitResult['output'] ?? '');
                }

                $retryDeployment->update([
                    'status' => 'success',
                    'completed_at' => now(),
                    'commit_hash' => $deployedCommitHash,
                ]);

                // Perform health check
                try {
                    $this->performHealthCheck($site);
                } catch (\Exception $e) {
                    Log::warning('Health check failed after retry', [
                        'site_id' => $site->id,
                        'deployment_id' => $retryDeployment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $retryDeployment->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => $result['error'],
                ]);
            }

            return $retryDeployment;
        } catch (\Exception $e) {
            Log::error('Retry failed', [
                'site_id' => $site->id,
                'original_deployment_id' => $failedDeployment->id,
                'retry_deployment_id' => $retryDeployment->id,
                'error' => $e->getMessage(),
            ]);

            $retryDeployment->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            return $retryDeployment;
        }
    }

    /**
     * Perform health check on site after deployment.
     */
    private function performHealthCheck(Site $site): void
    {
        $domain = $site->domain;
        $urls = [
            "https://{$domain}",
            "http://{$domain}",
        ];

        foreach ($urls as $url) {
            try {
                $startTime = microtime(true);
                
                $response = \Illuminate\Support\Facades\Http::timeout(10)
                    ->withoutRedirecting()
                    ->get($url);
                
                $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                $statusCode = $response->status();
                
                if ($statusCode >= 200 && $statusCode < 400) {
                    Log::info('Health check passed after deployment', [
                        'site_id' => $site->id,
                        'url' => $url,
                        'status_code' => $statusCode,
                        'response_time' => $responseTime,
                    ]);
                    return; // Success, exit
                }
            } catch (\Exception $e) {
                // Continue to next URL
                continue;
            }
        }

        Log::warning('Health check failed after deployment', [
            'site_id' => $site->id,
            'domain' => $domain,
        ]);
    }
}
