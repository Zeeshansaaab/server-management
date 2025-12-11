<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'domain',
        'system_user',
        'web_directory',
        'repository_url',
        'repository_branch',
        'php_version',
        'node_version',
        'framework',
        'environment_variables',
        'is_active',
        'current_release',
        'auto_deploy',
        'webhook_token',
        'deployment_script',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'auto_deploy' => 'boolean',
        ];
    }


    /**
     * Get the server that owns the site.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Get the deployments for the site.
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    /**
     * Get the latest deployment for the site.
     */
    public function latestDeployment()
    {
        return $this->hasOne(Deployment::class)->latestOfMany();
    }

    /**
     * Get the databases for the site.
     */
    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }

    /**
     * Get the SSL certificates for the site.
     */
    public function sslCertificates(): HasMany
    {
        return $this->hasMany(SslCertificate::class);
    }

    /**
     * Get the scheduled commands for the site.
     */
    public function scheduledCommands(): HasMany
    {
        return $this->hasMany(ScheduledCommand::class);
    }

    /**
     * Get the backups for the site.
     */
    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    /**
     * Get decrypted environment variables.
     */
    public function getDecryptedEnvironmentVariables(): array
    {
        if (empty($this->environment_variables)) {
            return [];
        }

        $decrypted = Crypt::decryptString($this->environment_variables);
        return json_decode($decrypted, true) ?? [];
    }

    /**
     * Set encrypted environment variables.
     */
    public function setEnvironmentVariables(array $vars): void
    {
        $this->environment_variables = Crypt::encryptString(json_encode($vars));
    }

    /**
     * Get the full web directory path.
     */
    public function getWebDirectoryPath(): string
    {
        return str_replace(
            ['{user}', '{domain}'],
            [$this->system_user, $this->domain],
            $this->web_directory
        );
    }

    /**
     * Get the releases directory path.
     */
    public function getReleasesPath(): string
    {
        return $this->getWebDirectoryPath() . '/releases';
    }

    /**
     * Get the current release path.
     */
    public function getCurrentPath(): string
    {
        return $this->getWebDirectoryPath() . '/current';
    }
}
