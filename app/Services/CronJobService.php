<?php

namespace App\Services;

use App\Models\Server;
use App\Models\Site;
use App\Models\ScheduledCommand;
use Illuminate\Support\Facades\Log;

class CronJobService
{
    public function __construct(
        private SshService $sshService
    ) {}

    /**
     * List all cron jobs for a site.
     */
    public function listCronJobs(Site $site): array
    {
        try {
            $server = $site->server;
            $user = $site->system_user;

            // Get crontab for the system user
            $result = $this->sshService->execute($server, "sudo -u {$user} crontab -l 2>/dev/null || echo ''");

            if (!$result['success']) {
                return [];
            }

            $crontab = $result['output'];
            $jobs = [];

            foreach (explode("\n", $crontab) as $line) {
                $line = trim($line);
                
                // Skip empty lines and comments
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }

                // Parse cron line: minute hour day month weekday command
                if (preg_match('/^(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(.+)$/', $line, $matches)) {
                    $jobs[] = [
                        'cron_expression' => "{$matches[1]} {$matches[2]} {$matches[3]} {$matches[4]} {$matches[5]}",
                        'command' => $matches[6],
                        'user' => $user,
                        'raw' => $line,
                    ];
                }
            }

            return $jobs;
        } catch (\Exception $e) {
            Log::error('Failed to list cron jobs', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Add a cron job to the server.
     */
    public function addCronJob(Site $site, string $cronExpression, string $command, ?string $user = null): bool
    {
        try {
            $server = $site->server;
            $user = $user ?? $site->system_user;

            // Get current crontab
            $result = $this->sshService->execute($server, "sudo -u {$user} crontab -l 2>/dev/null || echo ''");
            $crontab = $result['success'] ? trim($result['output']) : '';

            // Add new job
            $newJob = "{$cronExpression} {$command}";
            
            // Check if job already exists
            if (str_contains($crontab, $command)) {
                return false; // Job already exists
            }

            // Build new crontab
            $newCrontab = $crontab;
            if (!empty($newCrontab) && !str_ends_with($newCrontab, "\n")) {
                $newCrontab .= "\n";
            }
            $newCrontab .= $newJob . "\n";

            // Install new crontab using echo with heredoc-like approach
            $escapedCrontab = escapeshellarg($newCrontab);
            $installResult = $this->sshService->execute($server, "echo {$escapedCrontab} | sudo -u {$user} crontab -");

            return $installResult['success'];
        } catch (\Exception $e) {
            Log::error('Failed to add cron job', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Remove a cron job from the server.
     */
    public function removeCronJob(Site $site, string $command, ?string $user = null): bool
    {
        try {
            $server = $site->server;
            $user = $user ?? $site->system_user;

            // Get current crontab
            $result = $this->sshService->execute($server, "sudo -u {$user} crontab -l 2>/dev/null || echo ''");
            
            if (!$result['success']) {
                return false;
            }

            $crontab = $result['output'];
            $lines = explode("\n", $crontab);
            $newLines = [];

            foreach ($lines as $line) {
                if (!str_contains($line, $command)) {
                    $newLines[] = $line;
                }
            }

            $newCrontab = implode("\n", $newLines) . "\n";
            $escapedCrontab = escapeshellarg($newCrontab);
            
            $installResult = $this->sshService->execute($server, "echo {$escapedCrontab} | sudo -u {$user} crontab -");

            return $installResult['success'];
        } catch (\Exception $e) {
            Log::error('Failed to remove cron job', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Sync database cron jobs to server crontab.
     */
    public function syncCronJobs(Site $site): bool
    {
        try {
            $server = $site->server;
            $user = $site->system_user;

            // Get active cron jobs from database
            $scheduledCommands = ScheduledCommand::where('site_id', $site->id)
                ->where('is_active', true)
                ->get();

            // Build crontab content
            $crontabLines = ["# Crontab managed by Server Control\n"];
            
            foreach ($scheduledCommands as $command) {
                $crontabLines[] = "{$command->cron_expression} {$command->command}";
            }

            $crontab = implode("\n", $crontabLines) . "\n";
            $escapedCrontab = escapeshellarg($crontab);
            
            $result = $this->sshService->execute($server, "echo {$escapedCrontab} | sudo -u {$user} crontab -");

            return $result['success'];
        } catch (\Exception $e) {
            Log::error('Failed to sync cron jobs', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
