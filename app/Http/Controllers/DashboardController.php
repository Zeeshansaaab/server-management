<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Site;
use App\Models\Deployment;
use App\Models\ScheduledCommand;
use App\Services\SiteScannerService;
use App\Services\CronJobService;
use App\Services\DeploymentService;
use App\Services\SslService;
use App\Jobs\ScanSitesJob;
use App\Jobs\DeploySiteJob;
use App\Models\SslCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    /**
     * Show the dashboard.
     */
    public function index(): Response
    {
        $userId = auth()->id();
        $cacheKey = 'dashboard_user_' . $userId;
        
        $data = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($userId) {
            return [
                'servers' => Server::where('user_id', $userId)
                    ->with('latestMetric')
                    ->get(),
                'sites' => Site::whereHas('server', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })->with(['server', 'latestDeployment'])->get(),
                'recentDeployments' => Deployment::whereHas('site.server', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })->with(['site.server'])
                    ->latest()
                    ->limit(10)
                    ->get(),
            ];
        });

        return Inertia::render('Dashboard', $data);
    }

    /**
     * Show servers list.
     */
    public function servers(): Response
    {
        try {
            $cacheKey = 'servers_user_' . auth()->id();
            
            $servers = Cache::remember($cacheKey, now()->addMinutes(5), function () {
                return Server::where('user_id', auth()->id())
                    ->with('latestMetric')
                    ->get();
            });

            return Inertia::render('Servers/Index', [
                'servers' => $servers,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading servers: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Show server details.
     */
    public function server(Server $server): Response
    {
        // Ensure user owns this server
        if ($server->user_id !== auth()->id()) {
            abort(403);
        }
        
        // Try to update last_seen by testing connection (non-blocking, with timeout)
        try {
            $sshService = app(\App\Services\SshService::class);
            $testResult = $sshService->testConnection($server);
            // last_seen is updated automatically in SshService on successful connection
            // Refresh server to get updated last_seen
            $server->refresh();
        } catch (\Exception $e) {
            // Connection test failed, but continue to show page
            Log::debug('Connection test failed on server view', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);
        }
        
        // Use cache for server data (but refresh last_seen)
        $cacheKey = 'server_' . $server->id;
        $cachedServer = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($server) {
            $server->load(['sites.latestDeployment', 'databases', 'latestMetric']);
            return $server;
        });
        
        // But use fresh server to get updated last_seen
        $server->load(['sites.latestDeployment', 'databases', 'latestMetric']);

        return Inertia::render('Servers/Show', [
            'server' => $server,
        ]);
    }

    /**
     * Show sites list.
     */
    public function sites(): Response
    {
        $cacheKey = 'sites_user_' . auth()->id();
        
        $sites = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            return Site::whereHas('server', function ($query) {
                $query->where('user_id', auth()->id());
            })->with(['server', 'latestDeployment'])->get();
        });
        
        $serversCacheKey = 'servers_list_user_' . auth()->id();
        $servers = Cache::remember($serversCacheKey, now()->addMinutes(10), function () {
            return Server::where('user_id', auth()->id())->get();
        });

        return Inertia::render('Sites/Index', [
            'sites' => $sites,
            'servers' => $servers,
        ]);
    }

    /**
     * Show site details.
     */
    public function site(Site $site): Response
    {
        // Ensure user owns the server this site belongs to
        if ($site->server->user_id !== auth()->id()) {
            abort(403);
        }
        
        // Use cache for site data
        $cacheKey = 'site_' . $site->id;
        $cachedSite = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($site) {
            $site->load([
                'server',
                'deployments.logs',
                'databases',
                'sslCertificates',
                'scheduledCommands',
            ]);
            return $site;
        });
        
        // But always load fresh to get latest data
        $site->load([
            'server',
            'deployments' => function ($query) {
                $query->latest()->limit(10)->with('logs');
            },
            'latestDeployment.logs',
            'databases',
            'sslCertificates',
            'scheduledCommands',
        ]);

        return Inertia::render('Sites/Show', [
            'site' => $site,
        ]);
    }

    /**
     * Store a newly created server.
     */
    public function storeServer(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hostname' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'ssh_user' => 'nullable|string|max:255',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'ssh_private_key' => 'nullable|string',
            'ssh_public_key' => 'nullable|string',
        ]);

        $server = new Server($validated);
        $server->user_id = auth()->id();
        $server->ssh_user = $validated['ssh_user'] ?? 'root';
        $server->ssh_port = $validated['ssh_port'] ?? 22;
        $server->agent_token = Str::random(64);

        if (!empty($validated['ssh_private_key'])) {
            $server->setSshPrivateKey($validated['ssh_private_key']);
        }

        $server->save();

        // Clear cache
        Cache::forget('servers_user_' . auth()->id());
        Cache::forget('dashboard_user_' . auth()->id());

        // Test connection and update last_seen if successful
        try {
            $sshService = app(\App\Services\SshService::class);
            $testResult = $sshService->testConnection($server);
            if ($testResult['success']) {
                $server->update(['last_seen' => now()]);
            }
        } catch (\Exception $e) {
            // Connection test failed, but server was still saved
            Log::debug('Initial connection test failed for new server', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('servers.index')->with('success', 'Server added successfully!');
    }

    /**
     * Test SSH connection to a server.
     */
    public function testServerConnection(Request $request)
    {
        $validated = $request->validate([
            'hostname' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'ssh_user' => 'nullable|string|max:255',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'ssh_private_key' => 'nullable|string',
        ]);

        // Validate SSH key format if provided
        if (!empty($validated['ssh_private_key'])) {
            $key = trim($validated['ssh_private_key']);
            
            // Check if it looks like a valid SSH private key
            $validKeyPatterns = [
                '/^-----BEGIN (RSA |OPENSSH )?PRIVATE KEY-----/',
                '/^-----BEGIN EC PRIVATE KEY-----/',
                '/^-----BEGIN DSA PRIVATE KEY-----/',
            ];
            
            $isValidKey = false;
            foreach ($validKeyPatterns as $pattern) {
                if (preg_match($pattern, $key)) {
                    $isValidKey = true;
                    break;
                }
            }
            
            if (!$isValidKey) {
                return response()->json([
                    'connected' => false,
                    'message' => 'Invalid SSH private key format. The key should start with "-----BEGIN ... PRIVATE KEY-----"',
                    'error' => 'Invalid SSH key format',
                    'output' => null,
                    'exit_code' => null,
                ], 400);
            }
            
            // Check if key ends properly
            if (!preg_match('/-----END .* PRIVATE KEY-----\s*$/', $key)) {
                return response()->json([
                    'connected' => false,
                    'message' => 'Invalid SSH private key format. The key should end with "-----END ... PRIVATE KEY-----"',
                    'error' => 'Invalid SSH key format - missing end marker',
                    'output' => null,
                    'exit_code' => null,
                ], 400);
            }
            
            // Check for common issues
            // Check if key might be encrypted (has Proc-Type: 4,ENCRYPTED) - old format
            if (preg_match('/Proc-Type:\s*4,ENCRYPTED/i', $key)) {
                return response()->json([
                    'connected' => false,
                    'message' => 'This SSH key is encrypted with a passphrase (old PEM format). You need to remove the passphrase. Run: ssh-keygen -p -f ~/.ssh/your_key_file (enter current passphrase, then press Enter twice for no new passphrase)',
                    'error' => 'SSH key is encrypted with passphrase (PEM format)',
                    'output' => null,
                    'exit_code' => null,
                ], 400);
            }
            
            // Check if it's a public key (starts with ssh-rsa, ssh-ed25519, etc.)
            if (preg_match('/^(ssh-rsa|ssh-ed25519|ecdsa-sha2|ssh-dss)\s+/', $key)) {
                return response()->json([
                    'connected' => false,
                    'message' => 'You pasted a PUBLIC key. Please paste your PRIVATE key instead. Private keys start with "-----BEGIN ... PRIVATE KEY-----"',
                    'error' => 'Public key provided instead of private key',
                    'output' => null,
                    'exit_code' => null,
                ], 400);
            }
            
            // Note: We'll let the actual SSH connection attempt reveal if the key is encrypted
            // The "error in libcrypto" error will be caught and handled in the error response below
        } else {
            // Return early with helpful message if no key provided
            return response()->json([
                'connected' => false,
                'message' => 'SSH private key is required for connection testing. Please paste your SSH private key in the form above.',
                'error' => 'SSH private key not provided',
                'output' => null,
                'exit_code' => null,
            ]);
        }

        // Create a temporary server instance for testing
        $tempServer = new Server([
            'hostname' => $validated['hostname'],
            'ip_address' => $validated['ip_address'],
            'ssh_user' => $validated['ssh_user'] ?? 'root',
            'ssh_port' => $validated['ssh_port'] ?? 22,
        ]);
        Log::info('SSH private key', [$validated['ssh_private_key']]);
        $tempServer->setSshPrivateKey($validated['ssh_private_key']);

        try {
            $sshService = app(\App\Services\SshService::class);
            $result = $sshService->testConnection($tempServer);
            
            // Log the connection attempt
            Log::info('SSH connection test', [
                'hostname' => $validated['hostname'],
                'ip_address' => $validated['ip_address'],
                'ssh_user' => $validated['ssh_user'] ?? 'root',
                'ssh_port' => $validated['ssh_port'] ?? 22,
                'success' => $result['success'],
                'error' => $result['error'],
                'exit_code' => $result['exit_code'],
            ]);
            
            if ($result['success']) {
                // Update last_seen for the temp server (if it was saved)
                // For test connections, we'll update it when the actual server is used
                return response()->json([
                    'connected' => true,
                    'message' => 'Connection successful!',
                    'output' => $result['output'],
                ]);
            } else {
                // Build detailed error message
                $errorMessage = 'Connection failed. ';
                
                // Common SSH error messages and their meanings
                $sshError = trim($result['error']);
                
                if (str_contains($sshError, 'error in libcrypto') || str_contains($sshError, 'Load key')) {
                    $errorMessage = 'Invalid SSH private key format. ';
                    $errorMessage .= 'The key appears to be corrupted or in an unsupported format. ';
                    $errorMessage .= 'Please ensure you copied the ENTIRE key including the BEGIN and END lines. ';
                    $errorMessage .= 'If your key has a passphrase, you may need to remove it or use a key without a passphrase.';
                } elseif (str_contains($sshError, 'Permission denied') && str_contains($sshError, 'publickey')) {
                    $errorMessage .= 'Authentication failed. ';
                    $errorMessage .= 'The SSH key is not authorized on the server. ';
                    $errorMessage .= 'Make sure the corresponding public key is in the server\'s ~/.ssh/authorized_keys file.';
                } elseif (str_contains($sshError, 'Permission denied')) {
                    $errorMessage .= 'Authentication failed. ';
                    $errorMessage .= 'Check that the SSH user, key, and server credentials are correct.';
                } elseif (str_contains($sshError, 'Connection refused') || str_contains($sshError, 'Connection timed out')) {
                    $errorMessage .= 'Cannot reach the server. ';
                    $errorMessage .= 'Check if the IP address (' . $validated['ip_address'] . ') and SSH port (' . ($validated['ssh_port'] ?? 22) . ') are correct, ';
                    $errorMessage .= 'and that the server is accessible from this network.';
                } elseif (str_contains($sshError, 'Host key verification failed')) {
                    $errorMessage .= 'Host key verification failed. This is usually safe to ignore.';
                } elseif (str_contains($sshError, 'No such file or directory')) {
                    $errorMessage .= 'SSH key file issue. Please check your private key format.';
                } else {
                    $errorMessage .= 'Please check your server credentials and network connectivity.';
                }
                
                return response()->json([
                    'connected' => false,
                    'message' => $errorMessage,
                    'error' => $result['error'],
                    'output' => $result['output'],
                    'exit_code' => $result['exit_code'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SSH connection test exception', [
                'hostname' => $validated['hostname'] ?? null,
                'ip_address' => $validated['ip_address'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'connected' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ], 500);
        }
    }

    /**
     * Check server status and update last_seen.
     */
    public function checkServerStatus(Server $server)
    {
        if ($server->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $sshService = app(\App\Services\SshService::class);
            $result = $sshService->testConnection($server);
            
            // Refresh server to get updated last_seen
            $server->refresh();
            
            return response()->json([
                'success' => $result['success'],
                'is_online' => $server->is_online,
                'last_seen' => $server->last_seen,
                'message' => $result['success'] ? 'Server is online and accessible' : 'Server connection failed: ' . ($result['error'] ?? 'Unknown error'),
            ]);
        } catch (\Exception $e) {
            Log::error('Server status check failed', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'is_online' => false,
                'message' => 'Failed to check server status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Scan server for existing sites (queued).
     */
    public function scanSites(Server $server)
    {
        if ($server->user_id !== auth()->id()) {
            abort(403);
        }

        // Generate unique job ID
        $jobId = Str::uuid()->toString();

        // Dispatch job to queue
        ScanSitesJob::dispatch($server, $jobId);

        return response()->json([
            'success' => true,
            'job_id' => $jobId,
            'message' => 'Site scan started. Please wait...',
        ]);
    }

    /**
     * Check scan sites job status.
     */
    public function checkScanSitesStatus(Request $request, Server $server)
    {
        if ($server->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'job_id' => 'required|string',
        ]);

        $jobId = $validated['job_id'];
        $status = Cache::get("scan_sites_job_{$jobId}");

        if (!$status) {
            return response()->json([
                'success' => false,
                'status' => 'not_found',
                'message' => 'Job not found or expired',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'status' => $status['status'],
            'progress' => $status['progress'] ?? 0,
            'message' => $status['message'] ?? '',
            'sites' => $status['sites'] ?? null,
            'count' => $status['count'] ?? 0,
            'error' => $status['error'] ?? null,
        ]);
    }

    /**
     * Import detected sites.
     */
    public function importSites(Server $server, Request $request)
    {
        if ($server->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'sites' => 'required|array',
            'sites.*.domain' => 'required|string',
            'sites.*.web_directory' => 'nullable|string',
            'sites.*.system_user' => 'nullable|string',
            'sites.*.php_version' => 'nullable|string',
        ]);

        try {
            $scanner = app(SiteScannerService::class);
            $imported = [];

            foreach ($validated['sites'] as $siteData) {
                $site = $scanner->importSites($server, $siteData);
                $imported[] = $site;
            }

            // Clear cache
            Cache::forget('server_' . $server->id);
            Cache::forget('sites_user_' . auth()->id());
            Cache::forget('dashboard_user_' . auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Sites imported successfully',
                'sites' => $imported,
            ]);
        } catch (\Exception $e) {
            Log::error('Site import failed', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import sites: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created site.
     */
    public function storeSite(Request $request)
    {
        $validated = $request->validate([
            'server_id' => 'required|exists:servers,id',
            'domain' => 'required|string|max:255',
            'system_user' => 'nullable|string|max:255',
            'web_directory' => 'nullable|string|max:500',
            'repository_url' => 'nullable|url',
            'repository_branch' => 'nullable|string|max:255',
            'framework' => 'nullable|string|in:laravel,nextjs,react,nodejs,other',
            'node_version' => 'nullable|string|max:10',
            'php_version' => 'nullable|string|max:10',
            'environment_variables' => 'nullable|array',
        ]);

        $server = Server::findOrFail($validated['server_id']);
        
        if ($server->user_id !== auth()->id()) {
            abort(403);
        }

        $systemUser = $validated['system_user'] ?? 'forge_' . Str::slug($validated['domain']);
        // Use /var/www/html/{domain} as default for better compatibility
        $webDirectory = $validated['web_directory'] ?? '/var/www/html/' . $validated['domain'];

        // Determine default PHP/Node version based on framework
        $framework = $validated['framework'] ?? null;
        $phpVersion = $validated['php_version'] ?? null;
        $nodeVersion = $validated['node_version'] ?? null;
        
        // Set defaults based on framework
        if ($framework === 'laravel' || (!$framework && !$nodeVersion)) {
            $phpVersion = $phpVersion ?? '8.2';
        } elseif (in_array($framework, ['nextjs', 'react', 'nodejs'])) {
            $nodeVersion = $nodeVersion ?? '20'; // Default Node.js version for Next.js/React/Node.js
        }

        $siteData = [
            'server_id' => $validated['server_id'],
            'domain' => $validated['domain'],
            'system_user' => $systemUser,
            'web_directory' => $webDirectory,
            'repository_url' => $validated['repository_url'] ?? null,
            'repository_branch' => $validated['repository_branch'] ?? 'main',
            'framework' => $framework,
            'node_version' => $nodeVersion,
            'php_version' => $phpVersion,
            'is_active' => true,
            'auto_deploy' => false,
        ];

        // Generate webhook token if repository URL is provided
        if (!empty($validated['repository_url'])) {
            $siteData['webhook_token'] = Str::random(64);
        }

        $site = Site::create($siteData);

        if (!empty($validated['environment_variables'])) {
            $site->setEnvironmentVariables($validated['environment_variables']);
            $site->save();
        }

        // Clear cache
        Cache::forget('sites_user_' . auth()->id());
        Cache::forget('dashboard_user_' . auth()->id());

        return redirect()->route('sites.show', $site)->with('success', 'Site added successfully!');
    }

    /**
     * Re-check site repository and framework information.
     */
    public function recheckSite(Site $site)
    {
        if ($site->server->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $scanner = app(SiteScannerService::class);
            $detected = $scanner->recheckSite($site);

            return response()->json([
                'success' => true,
                'message' => 'Site information re-checked successfully',
                'detected' => $detected,
            ]);
        } catch (\Exception $e) {
            Log::error('Re-check site failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to re-check site: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a site.
     */
    public function updateSite(Site $site, Request $request)
    {
        if ($site->server->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'domain' => 'sometimes|string|max:255',
            'system_user' => 'nullable|string|max:255',
            'web_directory' => 'nullable|string|max:500',
            'repository_url' => 'nullable|url',
            'repository_branch' => 'nullable|string|max:255',
            'php_version' => 'nullable|string|max:10',
            'node_version' => 'nullable|string|max:10',
            'framework' => 'nullable|string|in:laravel,nextjs,react,nodejs',
            'deployment_script' => 'nullable|string',
            'auto_deploy' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        // Generate webhook token if auto_deploy is being enabled and repository_url exists
        if (isset($validated['auto_deploy']) && $validated['auto_deploy'] && empty($site->webhook_token)) {
            if (!empty($validated['repository_url']) || $site->repository_url) {
                $validated['webhook_token'] = Str::random(64);
            }
        }

        // If updating repository_url, ensure webhook_token exists if auto_deploy is enabled
        if (isset($validated['repository_url']) && $site->auto_deploy && empty($site->webhook_token)) {
            $validated['webhook_token'] = Str::random(64);
        }

        $site->update($validated);

        // Clear cache
        Cache::forget('site_' . $site->id);
        Cache::forget('sites_user_' . auth()->id());
        Cache::forget('dashboard_user_' . auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Site updated successfully',
            'site' => $site->fresh(['server', 'latestDeployment']),
        ]);
    }

    /**
     * Request SSL certificate for a site.
     */
    public function requestSslCertificate(Site $site)
    {
        if ($site->server->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $sslService = app(SslService::class);
            $certificate = $sslService->requestCertificate($site, $site->domain);

            return response()->json([
                'success' => $certificate->status === 'active',
                'certificate' => $certificate,
                'message' => $certificate->status === 'active' 
                    ? 'SSL certificate installed successfully' 
                    : 'Failed to install SSL certificate: ' . ($certificate->error_message ?? 'Unknown error'),
            ]);
        } catch (\Exception $e) {
            Log::error('SSL certificate request failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to request SSL certificate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Renew SSL certificate for a site.
     */
    public function renewSslCertificate(Site $site)
    {
        if ($site->server->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $certificate = $site->sslCertificates()
                ->where('domain', $site->domain)
                ->where('status', 'active')
                ->latest()
                ->first();

            if (!$certificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active SSL certificate found for this site',
                ], 404);
            }

            $sslService = app(SslService::class);
            $success = $sslService->renewCertificate($certificate);

            return response()->json([
                'success' => $success,
                'certificate' => $certificate->fresh(),
                'message' => $success 
                    ? 'SSL certificate renewed successfully' 
                    : 'Failed to renew SSL certificate: ' . ($certificate->error_message ?? 'Unknown error'),
            ]);
        } catch (\Exception $e) {
            Log::error('SSL certificate renewal failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to renew SSL certificate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check site health (HTTP status).
     */
    public function checkSiteHealth(Site $site)
    {
        try {
            // Build URL - try https first, fallback to http
            $domain = $site->domain;
            $urls = [
                "https://{$domain}",
                "http://{$domain}",
            ];

            $result = [
                'success' => false,
                'status_code' => null,
                'url' => null,
                'message' => 'Unable to reach site',
                'response_time' => null,
            ];

            foreach ($urls as $url) {
                try {
                    $startTime = microtime(true);
                    
                    // Make HTTP request with timeout
                    $response = Http::timeout(10)
                        ->withoutRedirecting()
                        ->get($url);
                    
                    $responseTime = round((microtime(true) - $startTime) * 1000, 2); // Convert to milliseconds
                    
                    $statusCode = $response->status();
                    
                    $result = [
                        'success' => $statusCode >= 200 && $statusCode < 400,
                        'status_code' => $statusCode,
                        'url' => $url,
                        'message' => $this->getStatusMessage($statusCode),
                        'response_time' => $responseTime,
                    ];
                    
                    // If we got a successful response, break
                    if ($result['success']) {
                        break;
                    }
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    // Connection failed, try next URL
                    continue;
                } catch (\Exception $e) {
                    // Other errors, log and continue
                    Log::debug('Site health check error', [
                        'site_id' => $site->id,
                        'url' => $url,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Site health check failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'status_code' => null,
                'url' => null,
                'message' => 'Error checking site health: ' . $e->getMessage(),
                'response_time' => null,
            ], 500);
        }
    }

    /**
     * Get human-readable status message.
     */
    private function getStatusMessage(int $statusCode): string
    {
        return match ($statusCode) {
            200 => 'OK - Site is responding normally',
            201 => 'Created',
            202 => 'Accepted',
            204 => 'No Content',
            301, 302, 303, 307, 308 => 'Redirect',
            400 => 'Bad Request - Server cannot process request',
            401 => 'Unauthorized - Authentication required',
            403 => 'Forbidden - Access denied',
            404 => 'Not Found - Page does not exist',
            500 => 'Internal Server Error - Server error',
            502 => 'Bad Gateway - Upstream server error',
            503 => 'Service Unavailable - Server temporarily unavailable',
            504 => 'Gateway Timeout - Server did not respond in time',
            default => "HTTP {$statusCode}",
        };
    }

    /**
     * Get deployment script for a site.
     */
    public function getDeploymentScript(Site $site)
    {
        if ($site->server->user_id !== auth()->id()) {
            abort(403);
        }

        // If custom script exists, return it
        if (!empty($site->deployment_script)) {
            return response()->json([
                'success' => true,
                'script' => $site->deployment_script,
                'is_custom' => true,
            ]);
        }

        // Generate default script based on framework
        $webDirectory = $site->getWebDirectoryPath();
        $releasesPath = $site->getReleasesPath();
        $currentPath = $site->getCurrentPath();
        
        $deploymentService = app(\App\Services\DeploymentService::class);
        $commands = $deploymentService->getDeploymentCommands(
            $site,
            $releasesPath . '/{release_id}',
            $currentPath,
            $releasesPath,
            $site->repository_branch ?? 'main'
        );

        // Format as script with placeholders
        $script = "# Deployment Script for {$site->domain}\n";
        $script .= "# Available placeholders: {release_path}, {current_path}, {releases_path}, {branch}, {domain}, {web_directory}\n";
        $script .= "# These will be replaced with actual values during deployment\n\n";
        
        foreach ($commands as $step => $command) {
            // Replace actual paths with placeholders for display
            $displayCommand = str_replace(
                [$releasesPath . '/{release_id}', $currentPath, $releasesPath, $webDirectory],
                ['{release_path}', '{current_path}', '{releases_path}', '{web_directory}'],
                $command
            );
            $script .= "# {$step}\n";
            $script .= $displayCommand . "\n\n";
        }

        return response()->json([
            'success' => true,
            'script' => $script,
            'is_custom' => false,
        ]);
    }

    /**
     * Deploy a site (queued).
     */
    public function deploySite(Site $site, Request $request)
    {
        if ($site->server->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'branch' => 'nullable|string|max:255',
        ]);

        // Generate unique job ID
        $jobId = Str::uuid()->toString();

        // Dispatch job to queue
        DeploySiteJob::dispatch($site, $validated['branch'] ?? null, $jobId);

        return response()->json([
            'success' => true,
            'job_id' => $jobId,
            'message' => 'Deployment started. Please wait...',
        ]);
    }

    /**
     * Check deployment job status.
     */
    public function checkDeploymentStatus(Request $request, Site $site)
    {
        if ($site->server->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'job_id' => 'required|string',
        ]);

        $jobId = $validated['job_id'];
        $status = Cache::get("deploy_site_job_{$jobId}");

        if (!$status) {
            return response()->json([
                'success' => false,
                'status' => 'not_found',
                'message' => 'Job not found or expired',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'status' => $status['status'],
            'progress' => $status['progress'] ?? 0,
            'message' => $status['message'] ?? '',
            'deployment' => $status['deployment'] ?? null,
            'error' => $status['error'] ?? null,
        ]);
    }

    /**
     * List cron jobs for a site.
     */
    public function listCronJobs(Site $site)
    {
        if ($site->server->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $cronService = app(CronJobService::class);
            $jobs = $cronService->listCronJobs($site);

            // Also get scheduled commands from database
            $scheduledCommands = ScheduledCommand::where('site_id', $site->id)->get();

            return response()->json([
                'success' => true,
                'server_jobs' => $jobs,
                'scheduled_commands' => $scheduledCommands,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list cron jobs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a cron job.
     */
    public function storeCronJob(Site $site, Request $request)
    {
        if ($site->server->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'command' => 'required|string|max:500',
            'cron_expression' => 'required|string|max:100',
            'user' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $scheduledCommand = ScheduledCommand::create([
                'site_id' => $site->id,
                'command' => $validated['command'],
                'cron_expression' => $validated['cron_expression'],
                'user' => $validated['user'] ?? $site->system_user,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Sync to server if active
            if ($scheduledCommand->is_active) {
                $cronService = app(CronJobService::class);
                $cronService->syncCronJobs($site);
            }

            // Clear cache
            Cache::forget('site_' . $site->id);

            return response()->json([
                'success' => true,
                'scheduled_command' => $scheduledCommand,
                'message' => 'Cron job added successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to add cron job', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add cron job: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a cron job.
     */
    public function deleteCronJob(ScheduledCommand $scheduledCommand)
    {
        $site = $scheduledCommand->site;
        
        if ($site->server->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $scheduledCommand->delete();

            // Sync to server
            $cronService = app(CronJobService::class);
            $cronService->syncCronJobs($site);

            // Clear cache
            Cache::forget('site_' . $site->id);

            return response()->json([
                'success' => true,
                'message' => 'Cron job deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete cron job: ' . $e->getMessage(),
            ], 500);
        }
    }
}
