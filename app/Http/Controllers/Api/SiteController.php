<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\Site;
use App\Services\SshService;
use App\Services\DatabaseService;
use App\Services\SslService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SiteController extends Controller
{
    public function __construct(
        private SshService $sshService,
        private DatabaseService $databaseService,
        private SslService $sslService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Site::with(['server', 'latestDeployment']);

        if ($request->has('server_id')) {
            $query->where('server_id', $request->server_id);
        }

        $sites = $query->get();

        return response()->json($sites);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'server_id' => 'required|exists:servers,id',
            'domain' => 'required|string|max:255',
            'repository_url' => 'required|url',
            'repository_branch' => 'nullable|string|max:255',
            'php_version' => 'nullable|string|max:10',
            'environment_variables' => 'nullable|array',
        ]);

        $server = Server::findOrFail($validated['server_id']);
        $this->authorize('view', $server);

        $systemUser = 'forge_' . Str::slug($validated['domain']);
        $webDirectory = '/home/' . $systemUser . '/sites/' . $validated['domain'];

        $site = Site::create([
            'server_id' => $validated['server_id'],
            'domain' => $validated['domain'],
            'system_user' => $systemUser,
            'web_directory' => $webDirectory,
            'repository_url' => $validated['repository_url'],
            'repository_branch' => $validated['repository_branch'] ?? 'main',
            'php_version' => $validated['php_version'] ?? '8.2',
        ]);

        if (!empty($validated['environment_variables'])) {
            $site->setEnvironmentVariables($validated['environment_variables']);
            $site->save();
        }

        // Create system user and directory structure
        $this->setupSiteOnServer($site, $server);

        // Create nginx config
        $this->createNginxConfig($site, $server);

        return response()->json($site->load('server'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Site $site)
    {
        $this->authorize('view', $site->server);
        
        $site->load(['server', 'deployments', 'databases', 'sslCertificates', 'scheduledCommands']);
        
        return response()->json($site);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Site $site)
    {
        $this->authorize('update', $site->server);

        $validated = $request->validate([
            'domain' => 'sometimes|string|max:255',
            'repository_url' => 'sometimes|url',
            'repository_branch' => 'sometimes|string|max:255',
            'php_version' => 'sometimes|string|max:10',
            'environment_variables' => 'sometimes|array',
        ]);

        if (isset($validated['environment_variables'])) {
            $site->setEnvironmentVariables($validated['environment_variables']);
            unset($validated['environment_variables']);
        }

        $site->update($validated);

        return response()->json($site);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Site $site)
    {
        $this->authorize('delete', $site->server);
        
        $site->delete();

        return response()->json(null, 204);
    }

    /**
     * Setup site on server.
     */
    private function setupSiteOnServer(Site $site, Server $server): void
    {
        $user = $site->system_user;
        $webDir = $site->getWebDirectoryPath();

        $commands = [
            "useradd -m -s /bin/bash $user",
            "mkdir -p $webDir/releases",
            "mkdir -p $webDir/shared",
            "chown -R $user:$user $webDir",
        ];

        foreach ($commands as $command) {
            $this->sshService->execute($server, $command, true);
        }
    }

    /**
     * Create nginx config.
     */
    private function createNginxConfig(Site $site, Server $server): void
    {
        $configPath = "/etc/nginx/sites-available/{$site->domain}";
        $webRoot = $site->getCurrentPath() . '/public';
        $phpVersion = $site->php_version;

        $config = <<<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name {$site->domain};

    root $webRoot;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php$phpVersion-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
NGINX;

        $commands = [
            "echo " . escapeshellarg($config) . " > $configPath",
            "ln -sf $configPath /etc/nginx/sites-enabled/{$site->domain}",
            "nginx -t && systemctl reload nginx",
        ];

        foreach ($commands as $command) {
            $this->sshService->execute($server, $command, true);
        }
    }
}
