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
    public function deploy(Site $site, ?string $branch = null): Deployment
    {
        $server = $site->server;
        $branch = $branch ?? $site->repository_branch ?? 'main';
        $webDirectory = $site->getWebDirectoryPath();
        $releasesPath = $site->getReleasesPath();
        $currentPath = $site->getCurrentPath();

        // Create deployment record
        $deployment = Deployment::create([
            'site_id' => $site->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $releaseId = date('YmdHis') . '_' . Str::random(8);
            $releasePath = $releasesPath . '/' . $releaseId;

            // Get deployment commands (custom script or default)
            $commands = $this->getDeploymentCommands($site, $releasePath, $currentPath, $releasesPath, $branch);

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
                $commitHash = null;
                if ($site->repository_url) {
                    // If using custom deployment script, try web directory first (common for direct deployments)
                    // Otherwise, try release path (for release-based deployments)
                    $webDirectory = $site->getWebDirectoryPath();
                    $pathsToTry = [];
                    
                    if (!empty($site->deployment_script)) {
                        // Custom script - likely deploying directly to web directory
                        $pathsToTry[] = $webDirectory;
                        $pathsToTry[] = $releasePath; // Fallback to release path
                    } else {
                        // Default script - using release-based deployment
                        $pathsToTry[] = $releasePath;
                        $pathsToTry[] = $webDirectory; // Fallback to web directory
                    }
                    
                    foreach ($pathsToTry as $path) {
                        $commitResult = $this->sshService->execute($server, 'bash -c ' . escapeshellarg("cd " . escapeshellarg($path) . " && git rev-parse HEAD 2>/dev/null || echo ''"), false);
                        $commitHash = trim($commitResult['output'] ?? '');
                        if (!empty($commitHash)) {
                            break; // Found commit hash, stop trying other paths
                        }
                    }
                }

                $deployment->update([
                    'status' => 'success',
                    'completed_at' => now(),
                    'release_path' => $releasePath,
                    'commit_hash' => $commitHash,
                ]);

                // Update site's current release
                $site->update(['current_release' => $releaseId]);
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
    public function getDeploymentCommands(Site $site, string $releasePath, string $currentPath, string $releasesPath, string $branch): array
    {
        // If custom deployment script exists, use it
        if (!empty($site->deployment_script)) {
            $script = $site->deployment_script;
            
            // Replace placeholders
            $script = str_replace(
                ['{release_path}', '{current_path}', '{releases_path}', '{branch}', '{domain}', '{web_directory}'],
                [$releasePath, $currentPath, $releasesPath, $branch, $site->domain, $site->getWebDirectoryPath()],
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

        // 1. Create releases directory if it doesn't exist
        $commands['create_releases_dir'] = "mkdir -p {$releasesPath}";

        // 2. Clone or update repository
        if ($site->repository_url) {
            if ($this->directoryExists($site->server, $releasePath)) {
                // Update existing - wrap in bash -c since it contains cd
                $commands['git_update'] = 'bash -c ' . escapeshellarg("cd {$releasePath} && git fetch origin && git reset --hard origin/{$branch}");
            } else {
                // Clone new
                $commands['git_clone'] = "git clone -b {$branch} {$site->repository_url} {$releasePath}";
            }
        } else {
            // No repository, just create directory structure
            $commands['create_release_dir'] = "mkdir -p {$releasePath}";
        }

        // Determine framework
        $isLaravel = $site->framework === 'laravel' || (!$site->framework && !$site->node_version);
        $isNextJs = $site->framework === 'nextjs' || $site->framework === 'react' || $site->node_version;

        // 3. Install dependencies based on framework
        if ($isLaravel) {
            // Laravel: Install composer dependencies - wrap in bash -c since it contains cd
            $commands['composer_install'] = 'bash -c ' . escapeshellarg("cd {$releasePath} && [ -f composer.json ] && composer install --no-dev --optimize-autoloader --no-interaction || true");
        }
        
        if ($isNextJs || $this->fileExists($site->server, $releasePath . '/package.json')) {
            // Node.js/Next.js: Install npm dependencies - wrap in bash -c since it contains cd
            $nodeVersion = $site->node_version ? "nvm use {$site->node_version} && " : "";
            $commands['npm_install'] = 'bash -c ' . escapeshellarg("cd {$releasePath} && [ -f package.json ] && ({$nodeVersion}npm ci || {$nodeVersion}npm install) || true");
        }

        // 4. Build assets based on framework
        if ($isNextJs) {
            // Next.js: Build production - wrap in bash -c since it contains cd
            $nodeVersion = $site->node_version ? "nvm use {$site->node_version} && " : "";
            $commands['npm_build'] = 'bash -c ' . escapeshellarg("cd {$releasePath} && [ -f package.json ] && [ -f next.config.js ] && {$nodeVersion}npm run build || true");
        } elseif ($isLaravel) {
            // Laravel: Build frontend assets if Mix/Vite exists - wrap in bash -c since it contains cd
            $nodeVersion = $site->node_version ? "nvm use {$site->node_version} && " : "";
            $commands['npm_build'] = 'bash -c ' . escapeshellarg("cd {$releasePath} && [ -f package.json ] && ({$nodeVersion}npm run build || {$nodeVersion}npm run production) || true");
        }

        // 5. Copy .env file from current release if exists
        $commands['copy_env'] = "[ -f {$currentPath}/.env ] && cp {$currentPath}/.env {$releasePath}/.env || true";

        // 6. Framework-specific setup
        if ($isLaravel) {
            // Laravel: Cache config, routes, views - wrap in bash -c since it contains cd
            $commands['laravel_cache'] = 'bash -c ' . escapeshellarg("cd {$releasePath} && [ -f artisan ] && php artisan config:cache && php artisan route:cache && php artisan view:cache || true");
            // Run migrations - wrap in bash -c since it contains cd
            $commands['laravel_migrate'] = 'bash -c ' . escapeshellarg("cd {$releasePath} && [ -f artisan ] && php artisan migrate --force || true");
        }

        // 7. Run deployment hooks (if deploy script exists) - wrap in bash -c since it contains cd
        $commands['deploy_hook'] = 'bash -c ' . escapeshellarg("cd {$releasePath} && [ -f deploy.sh ] && bash deploy.sh || true");

        // 8. Create symlink to new release
        $commands['create_symlink'] = "ln -sfn {$releasePath} {$currentPath}";

        // 9. Restart services based on framework
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
     * Rollback to previous release.
     */
    public function rollback(Site $site, ?string $releaseId = null): bool
    {
        $server = $site->server;
        $releasesPath = $site->getReleasesPath();
        $currentPath = $site->getCurrentPath();

        try {
            if ($releaseId) {
                // Rollback to specific release
                $releasePath = $releasesPath . '/' . $releaseId;
            } else {
                // Rollback to previous release
                $result = $this->sshService->execute($server, "ls -t {$releasesPath} | head -2 | tail -1");
                if (!$result['success'] || empty(trim($result['output']))) {
                    return false;
                }
                $releaseId = trim($result['output']);
                $releasePath = $releasesPath . '/' . $releaseId;
            }

            // Create symlink to previous release
            $result = $this->sshService->execute($server, "ln -sfn {$releasePath} {$currentPath}", true);

            if ($result['success']) {
                $site->update(['current_release' => $releaseId]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Rollback failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
