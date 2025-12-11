<?php

namespace App\Services;

use App\Models\Server;
use App\Models\Site;
use Illuminate\Support\Facades\Log;

class SiteScannerService
{
    public function __construct(
        private SshService $sshService
    ) {}

    /**
     * Scan server for existing websites.
     * Checks nginx/apache configs and common web directories.
     */
    public function scanForSites(Server $server): array
    {
        $detectedSites = [];
        $startTime = microtime(true);
        $maxExecutionTime = 25; // Leave 5 seconds buffer for response

        try {
            // Method 1: Scan Nginx configs (fastest, most reliable)
            if ((microtime(true) - $startTime) < $maxExecutionTime) {
                $nginxSites = $this->scanNginxConfigs($server);
                $detectedSites = array_merge($detectedSites, $nginxSites);
            }

            // Method 2: Scan Apache configs (if time permits)
            if ((microtime(true) - $startTime) < $maxExecutionTime) {
                $apacheSites = $this->scanApacheConfigs($server);
                $detectedSites = array_merge($detectedSites, $apacheSites);
            }

            // Method 3: Scan common web directories (skip if running out of time)
            if ((microtime(true) - $startTime) < $maxExecutionTime) {
                $directorySites = $this->scanWebDirectories($server);
                $detectedSites = array_merge($detectedSites, $directorySites);
            }

            // Remove duplicates based on domain
            $uniqueSites = [];
            foreach ($detectedSites as $site) {
                $domain = $site['domain'];
                if (!isset($uniqueSites[$domain])) {
                    $uniqueSites[$domain] = $site;
                } else {
                    // Merge information if we have more details
                    $uniqueSites[$domain] = array_merge($uniqueSites[$domain], $site);
                }
            }

            return array_values($uniqueSites);
        } catch (\Exception $e) {
            Log::error('Site scan failed', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw so controller can handle it
        }
    }

    /**
     * Scan Nginx configuration files for server blocks.
     */
    private function scanNginxConfigs(Server $server): array
    {
        $sites = [];

        try {
            // Check common nginx config locations
            $configPaths = [
                '/etc/nginx/sites-enabled',
                '/etc/nginx/conf.d',
                '/usr/local/nginx/conf/sites-enabled',
            ];

            foreach ($configPaths as $configPath) {
                // Use timeout to prevent hanging
                $result = $this->sshService->execute($server, "timeout 5 ls -1 {$configPath} 2>/dev/null | head -10");
                
                if ($result['success'] && !empty($result['output'])) {
                    $configFiles = array_filter(explode("\n", trim($result['output'])));
                    
                    // Limit to first 10 files to prevent timeout
                    $configFiles = array_slice($configFiles, 0, 10);
                    
                    foreach ($configFiles as $configFile) {
                        $fullPath = rtrim($configPath, '/') . '/' . trim($configFile);
                        $siteInfo = $this->parseNginxConfig($server, $fullPath);
                        
                        if ($siteInfo) {
                            $sites[] = $siteInfo;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('Nginx scan error', ['error' => $e->getMessage()]);
        }

        return $sites;
    }

    /**
     * Parse a single Nginx config file.
     */
    private function parseNginxConfig(Server $server, string $configPath): ?array
    {
        try {
            // Use timeout and limit file size to prevent hanging on large files
            $result = $this->sshService->execute($server, "timeout 3 head -100 " . escapeshellarg($configPath) . " 2>/dev/null");
            
            if (!$result['success'] || empty($result['output'])) {
                return null;
            }

            $config = $result['output'];
            $siteInfo = [
                'source' => 'nginx',
                'config_path' => $configPath,
            ];

            // Extract server_name (domain)
            if (preg_match('/server_name\s+([^;]+);/', $config, $matches)) {
                $serverNames = preg_split('/\s+/', trim($matches[1]));
                $siteInfo['domain'] = trim($serverNames[0], ';');
            } else {
                return null; // No domain found
            }

            // Extract root directory
            if (preg_match('/root\s+([^;]+);/', $config, $matches)) {
                $siteInfo['web_directory'] = trim($matches[1]);
            }

            // Extract PHP version from fastcgi_pass
            if (preg_match('/fastcgi_pass\s+unix:([^;]+);/', $config, $matches)) {
                $phpSocket = $matches[1];
                if (preg_match('/php(\d+\.\d+)/', $phpSocket, $phpMatch)) {
                    $siteInfo['php_version'] = $phpMatch[1];
                }
            }

            // Try to detect system user from directory path
            if (isset($siteInfo['web_directory'])) {
                if (preg_match('/\/home\/([^\/]+)/', $siteInfo['web_directory'], $userMatch)) {
                    $siteInfo['system_user'] = $userMatch[1];
                }
            }

            // Detect repository and framework if web_directory is available
            if (isset($siteInfo['web_directory'])) {
                $repoInfo = $this->detectRepository($server, $siteInfo['web_directory']);
                if ($repoInfo) {
                    $siteInfo = array_merge($siteInfo, $repoInfo);
                }
            }

            return $siteInfo;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Scan Apache configuration files.
     */
    private function scanApacheConfigs(Server $server): array
    {
        $sites = [];

        try {
            // Check common apache config locations
            $configPaths = [
                '/etc/apache2/sites-enabled',
                '/etc/httpd/conf.d',
                '/usr/local/apache2/conf/sites-enabled',
            ];

            foreach ($configPaths as $configPath) {
                // Use timeout to prevent hanging
                $result = $this->sshService->execute($server, "timeout 5 ls -1 {$configPath}/*.conf 2>/dev/null | head -10");
                
                if ($result['success'] && !empty($result['output'])) {
                    $configFiles = array_filter(explode("\n", trim($result['output'])));
                    
                    // Limit to first 10 files
                    $configFiles = array_slice($configFiles, 0, 10);
                    
                    foreach ($configFiles as $configFile) {
                        $siteInfo = $this->parseApacheConfig($server, trim($configFile));
                        
                        if ($siteInfo) {
                            $sites[] = $siteInfo;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('Apache scan error', ['error' => $e->getMessage()]);
        }

        return $sites;
    }

    /**
     * Parse a single Apache config file.
     */
    private function parseApacheConfig(Server $server, string $configPath): ?array
    {
        try {
            // Use timeout and limit file size
            $result = $this->sshService->execute($server, "timeout 3 head -100 " . escapeshellarg($configPath) . " 2>/dev/null");
            
            if (!$result['success'] || empty($result['output'])) {
                return null;
            }

            $config = $result['output'];
            $siteInfo = [
                'source' => 'apache',
                'config_path' => $configPath,
            ];

            // Extract ServerName or ServerAlias
            if (preg_match('/ServerName\s+([^\s]+)/', $config, $matches)) {
                $siteInfo['domain'] = trim($matches[1]);
            } elseif (preg_match('/ServerAlias\s+([^\s]+)/', $config, $matches)) {
                $siteInfo['domain'] = trim($matches[1]);
            } else {
                return null;
            }

            // Extract DocumentRoot
            if (preg_match('/DocumentRoot\s+"?([^"\s]+)"?/', $config, $matches)) {
                $siteInfo['web_directory'] = trim($matches[1]);
            }

            // Try to detect system user
            if (isset($siteInfo['web_directory'])) {
                if (preg_match('/\/home\/([^\/]+)/', $siteInfo['web_directory'], $userMatch)) {
                    $siteInfo['system_user'] = $userMatch[1];
                }
            }

            // Detect repository and framework if web_directory is available
            if (isset($siteInfo['web_directory'])) {
                $repoInfo = $this->detectRepository($server, $siteInfo['web_directory']);
                if ($repoInfo) {
                    $siteInfo = array_merge($siteInfo, $repoInfo);
                }
            }

            return $siteInfo;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Scan common web directories for sites.
     */
    private function scanWebDirectories(Server $server): array
    {
        $sites = [];

        try {
            // Common web directory patterns
            $patterns = [
                '/home/*/public_html',
                '/home/*/www',
                '/home/*/sites/*',
                '/var/www/*',
                '/var/www/html/*',
            ];

            // Limit directory scanning to prevent timeout
            $commonPaths = [
                '/home',
                '/var/www',
            ];
            
            foreach ($commonPaths as $basePath) {
                // Use timeout and limit depth/results
                $result = $this->sshService->execute($server, "timeout 5 find {$basePath} -maxdepth 2 -type d \\( -name public_html -o -name www \\) 2>/dev/null | head -10");
                
                if ($result['success'] && !empty($result['output'])) {
                    $directories = array_filter(explode("\n", trim($result['output'])));
                    
                    // Limit to first 10 directories
                    $directories = array_slice($directories, 0, 10);
                    
                    foreach ($directories as $directory) {
                        $directory = trim($directory);
                        if (preg_match('/\/([^\/]+)\/(public_html|www|sites\/([^\/]+))$/', $directory, $matches)) {
                            $user = $matches[1];
                            $domain = $matches[3] ?? $user;
                            
                            $sites[] = [
                                'source' => 'directory',
                                'domain' => $domain,
                                'web_directory' => $directory,
                                'system_user' => $user,
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('Directory scan error', ['error' => $e->getMessage()]);
        }

        return $sites;
    }

    /**
     * Detect repository information and framework from site directory.
     */
    private function detectRepository(Server $server, string $webDirectory): ?array
    {
        try {
            $info = [];
            
            // Check for .git directory
            $gitConfigPath = rtrim($webDirectory, '/') . '/.git/config';
            $result = $this->sshService->execute($server, "timeout 3 cat " . escapeshellarg($gitConfigPath) . " 2>/dev/null");
            
            if ($result['success'] && !empty($result['output'])) {
                $gitConfig = $result['output'];
                
                // Extract repository URL
                if (preg_match('/url\s*=\s*([^\s]+)/', $gitConfig, $matches)) {
                    $info['repository_url'] = trim($matches[1]);
                }
                
                // Get current branch
                $branchResult = $this->sshService->execute($server, "cd " . escapeshellarg($webDirectory) . " && timeout 3 git rev-parse --abbrev-ref HEAD 2>/dev/null");
                if ($branchResult['success'] && !empty($branchResult['output'])) {
                    $info['repository_branch'] = trim($branchResult['output']);
                }
            }
            
            // Check for package.json to detect Node.js/Next.js
            $packageJsonPath = rtrim($webDirectory, '/') . '/package.json';
            $packageResult = $this->sshService->execute($server, "timeout 3 cat " . escapeshellarg($packageJsonPath) . " 2>/dev/null");
            
            if ($packageResult['success'] && !empty($packageResult['output'])) {
                $packageJson = json_decode($packageResult['output'], true);
                
                if ($packageJson && isset($packageJson['dependencies'])) {
                    $dependencies = array_merge(
                        $packageJson['dependencies'] ?? [],
                        $packageJson['devDependencies'] ?? []
                    );
                    
                    // Detect Next.js
                    if (isset($dependencies['next'])) {
                        $info['framework'] = 'nextjs';
                        
                        // Get Node.js version from .nvmrc or detect installed version
                        $nvmrcResult = $this->sshService->execute($server, "timeout 3 cat " . escapeshellarg(rtrim($webDirectory, '/') . '/.nvmrc') . " 2>/dev/null");
                        if ($nvmrcResult['success'] && !empty($nvmrcResult['output'])) {
                            $info['node_version'] = trim($nvmrcResult['output']);
                        } else {
                            // Try to get Node version from server
                            $nodeResult = $this->sshService->execute($server, "timeout 3 node --version 2>/dev/null");
                            if ($nodeResult['success'] && !empty($nodeResult['output'])) {
                                $info['node_version'] = trim(str_replace('v', '', $nodeResult['output']));
                            }
                        }
                    } elseif (isset($dependencies['react'])) {
                        $info['framework'] = 'react';
                    } else {
                        $info['framework'] = 'nodejs';
                    }
                }
            }
            
            return !empty($info) ? $info : null;
        } catch (\Exception $e) {
            Log::debug('Repository detection error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Re-check and detect repository and framework information for an existing site.
     */
    public function recheckSite(Site $site): array
    {
        $server = $site->server;
        $webDirectory = $site->getWebDirectoryPath();
        
        $detected = [
            'repository_url' => $site->repository_url,
            'repository_branch' => $site->repository_branch,
            'framework' => $site->framework,
            'node_version' => $site->node_version,
            'php_version' => $site->php_version,
            'web_directory' => $site->web_directory,
        ];
        
        try {
            // Detect repository and framework from directory
            $repoInfo = $this->detectRepository($server, $webDirectory);
            if ($repoInfo) {
                $detected = array_merge($detected, $repoInfo);
            }
            
            // Detect PHP version if Laravel or no Node.js detected
            if (!$detected['node_version'] || $detected['framework'] === 'laravel') {
                // Try to detect PHP version from server or config
                $phpResult = $this->sshService->execute($server, "php -v 2>/dev/null | head -1 | grep -oP 'PHP \K[0-9]+\.[0-9]+' || echo ''");
                if ($phpResult['success'] && !empty(trim($phpResult['output']))) {
                    $detected['php_version'] = trim($phpResult['output']);
                }
                
                // Check for Laravel specific files
                if ($this->sshService->execute($server, "test -f " . escapeshellarg($webDirectory . '/artisan') . " && echo 'exists' || echo ''")['output'] === 'exists') {
                    $detected['framework'] = 'laravel';
                }
            }
            
            return $detected;
        } catch (\Exception $e) {
            Log::error('Re-check site failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);
            return $detected;
        }
    }

    /**
     * Import detected sites into database.
     */
    public function importSites(Server $server, array $siteData): Site
    {
        // Check if site already exists
        $existingSite = Site::where('server_id', $server->id)
            ->where('domain', $siteData['domain'])
            ->first();

            // Normalize web_directory - use /var/www/html/{domain} pattern
            $webDirectory = $siteData['web_directory'] ?? '/var/www/html/' . $siteData['domain'];
            
            // If web_directory contains placeholder patterns, replace them
            if (!empty($siteData['web_directory'])) {
                $webDirectory = str_replace(
                    ['{user}', '{domain}'],
                    [$siteData['system_user'] ?? 'www-data', $siteData['domain']],
                    $siteData['web_directory']
                );
            } else {
                $webDirectory = '/var/www/html/' . $siteData['domain'];
            }
            
            // Ensure it's an absolute path
            if (strpos($webDirectory, '/var/www/html/') !== 0 && strpos($webDirectory, '/home/') !== 0 && strpos($webDirectory, '/') !== 0) {
                $webDirectory = '/var/www/html/' . $siteData['domain'];
            }

        if ($existingSite) {
            // Update existing site with new information
            $updateData = [
                'web_directory' => $webDirectory,
                'system_user' => $siteData['system_user'] ?? $existingSite->system_user ?? 'www-data',
            ];
            
            if (isset($siteData['repository_url'])) {
                $updateData['repository_url'] = $siteData['repository_url'];
            }
            if (isset($siteData['repository_branch'])) {
                $updateData['repository_branch'] = $siteData['repository_branch'];
            }
            if (isset($siteData['node_version'])) {
                $updateData['node_version'] = $siteData['node_version'];
            }
            if (isset($siteData['php_version'])) {
                $updateData['php_version'] = $siteData['php_version'];
            }
            
            $existingSite->update($updateData);
            return $existingSite;
        }

        // Create new site
        $siteDataToCreate = [
            'server_id' => $server->id,
            'domain' => $siteData['domain'],
            'system_user' => $siteData['system_user'] ?? 'www-data',
            'web_directory' => $webDirectory,
            'is_active' => true,
        ];
        
        // Only set PHP version if no node_version (not a Node.js site)
        if (!isset($siteData['node_version'])) {
            $siteDataToCreate['php_version'] = $siteData['php_version'] ?? '8.2';
        }
        
        if (isset($siteData['repository_url'])) {
            $siteDataToCreate['repository_url'] = $siteData['repository_url'];
        }
        if (isset($siteData['repository_branch'])) {
            $siteDataToCreate['repository_branch'] = $siteData['repository_branch'];
        }
        if (isset($siteData['node_version'])) {
            $siteDataToCreate['node_version'] = $siteData['node_version'];
        }
        if (isset($siteData['framework'])) {
            $siteDataToCreate['framework'] = $siteData['framework'];
        }
        
        // Generate webhook token if repository URL is provided
        if (isset($siteDataToCreate['repository_url'])) {
            $siteDataToCreate['webhook_token'] = \Illuminate\Support\Str::random(64);
        }

        return Site::create($siteDataToCreate);
    }
}
