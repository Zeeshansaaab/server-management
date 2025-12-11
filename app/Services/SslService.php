<?php

namespace App\Services;

use App\Models\Site;
use App\Models\SslCertificate;
use App\Services\SshService;

class SslService
{
    public function __construct(
        private SshService $sshService
    ) {}

    /**
     * Request SSL certificate via Let's Encrypt.
     */
    public function requestCertificate(Site $site, string $domain): SslCertificate
    {
        $certificate = SslCertificate::create([
            'site_id' => $site->id,
            'domain' => $domain,
            'status' => 'pending',
        ]);

        $server = $site->server;
        
        // Determine webroot based on framework
        $webRoot = $this->getWebRoot($site);

        // Install certbot if not installed
        $this->ensureCertbotInstalled($server);

        // Use standalone mode for initial certificate (works for any web server)
        // This requires port 80 to be available
        $command = sprintf(
            'certbot certonly --standalone -d %s --non-interactive --agree-tos --email %s --preferred-challenges http',
            escapeshellarg($domain),
            escapeshellarg(config('mail.from.address', 'admin@example.com'))
        );

        $result = $this->sshService->execute($server, $command, true);

        if ($result['success']) {
            $certPath = "/etc/letsencrypt/live/$domain";
            
            // Get actual expiration date from certificate
            $expiresAt = $this->getCertificateExpiration($server, $certPath);
            
            $certificate->update([
                'status' => 'active',
                'certificate_path' => "$certPath/fullchain.pem",
                'private_key_path' => "$certPath/privkey.pem",
                'issued_at' => now(),
                'expires_at' => $expiresAt ?? now()->addDays(90),
            ]);

            // Update nginx config
            $this->updateNginxConfig($site, $domain, $certPath);
        } else {
            $certificate->update([
                'status' => 'failed',
                'error_message' => $result['error'],
            ]);
        }

        return $certificate;
    }

    /**
     * Renew SSL certificate.
     */
    public function renewCertificate(SslCertificate $certificate): bool
    {
        $site = $certificate->site;
        $server = $site->server;
        $domain = $certificate->domain;

        $certificate->update(['status' => 'pending']);

        // Install certbot if not installed
        $this->ensureCertbotInstalled($server);

        // Renew certificate
        $command = sprintf(
            'certbot renew --cert-name %s --non-interactive',
            escapeshellarg($domain)
        );

        $result = $this->sshService->execute($server, $command, true);

        if ($result['success']) {
            $certPath = "/etc/letsencrypt/live/$domain";
            $expiresAt = $this->getCertificateExpiration($server, $certPath);
            
            $certificate->update([
                'status' => 'active',
                'issued_at' => now(),
                'expires_at' => $expiresAt ?? now()->addDays(90),
                'error_message' => null,
            ]);

            // Reload nginx to use new certificate
            $this->sshService->execute($server, 'systemctl reload nginx', true);
            
            return true;
        } else {
            $certificate->update([
                'status' => 'failed',
                'error_message' => $result['error'],
            ]);
            
            return false;
        }
    }

    /**
     * Get web root directory based on framework.
     */
    private function getWebRoot(Site $site): string
    {
        $webDirectory = $site->getWebDirectoryPath();
        
        // For Laravel, use public directory
        if ($site->framework === 'laravel' || (!$site->framework && !$site->node_version)) {
            return $webDirectory . '/public';
        }
        
        // For Next.js/React/Node.js, use the web directory directly
        return $webDirectory;
    }

    /**
     * Get certificate expiration date.
     */
    private function getCertificateExpiration($server, string $certPath): ?\DateTime
    {
        $command = sprintf(
            'openssl x509 -enddate -noout -in %s/fullchain.pem 2>/dev/null | cut -d= -f2',
            escapeshellarg($certPath)
        );
        
        $result = $this->sshService->execute($server, $command, false);
        
        if ($result['success'] && !empty(trim($result['output']))) {
            try {
                $dateString = trim($result['output']);
                return new \DateTime($dateString);
            } catch (\Exception $e) {
                return null;
            }
        }
        
        return null;
    }

    /**
     * Ensure certbot is installed.
     */
    private function ensureCertbotInstalled($server): void
    {
        $command = 'which certbot || (apt-get update && apt-get install -y certbot python3-certbot-nginx)';
        $this->sshService->execute($server, $command, true);
    }

    /**
     * Update nginx config with SSL.
     */
    private function updateNginxConfig(Site $site, string $domain, string $certPath): void
    {
        $server = $site->server;
        $configPath = "/etc/nginx/sites-available/$domain";
        
        $config = $this->generateNginxSslConfig($site, $domain, $certPath);
        
        $command = "echo " . escapeshellarg($config) . " > $configPath && nginx -t && systemctl reload nginx";
        $this->sshService->execute($server, $command, true);
    }

    /**
     * Generate nginx SSL config.
     */
    private function generateNginxSslConfig(Site $site, string $domain, string $certPath): string
    {
        $webDirectory = $site->getWebDirectoryPath();
        $isLaravel = $site->framework === 'laravel' || (!$site->framework && !$site->node_version);
        $isNextJs = in_array($site->framework, ['nextjs', 'react', 'nodejs']) || $site->node_version;
        
        $webRoot = $isLaravel ? $webDirectory . '/public' : $webDirectory;
        $phpVersion = $site->php_version ?? '8.2';

        // Base SSL config
        $sslConfig = <<<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name $domain;
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name $domain;

    ssl_certificate $certPath/fullchain.pem;
    ssl_certificate_key $certPath/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    root $webRoot;
    index index.php index.html index.htm;

NGINX;

        // Add framework-specific config
        if ($isLaravel) {
            $sslConfig .= <<<NGINX
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php$phpVersion-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

NGINX;
        } elseif ($isNextJs) {
            // Next.js/React/Node.js - proxy to Node.js app (assuming it runs on port 3000)
            $sslConfig .= <<<NGINX
    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_cache_bypass \$http_upgrade;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

NGINX;
        } else {
            // Generic static site
            $sslConfig .= <<<NGINX
    location / {
        try_files \$uri \$uri/ =404;
    }

NGINX;
        }

        $sslConfig .= <<<NGINX
    location ~ /\.ht {
        deny all;
    }
}
NGINX;

        return $sslConfig;
    }
}

