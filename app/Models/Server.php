<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'hostname',
        'ip_address',
        'os',
        'ssh_user',
        'ssh_port',
        'ssh_private_key',
        'ssh_public_key',
        'agent_token',
        'last_seen',
        'tags',
        'free_disk_gb',
        'cpu_info',
        'memory_mb',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_seen' => 'datetime',
            'tags' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($server) {
            if (empty($server->agent_token)) {
                $server->agent_token = Str::random(64);
            }
        });
    }

    /**
     * Get the user that owns the server.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sites for the server.
     */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    /**
     * Get the databases for the server.
     */
    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }

    /**
     * Get the metrics for the server.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(ServerMetric::class);
    }

    /**
     * Get the latest metric for the server.
     */
    public function latestMetric()
    {
        return $this->hasOne(ServerMetric::class)->latestOfMany('recorded_at');
    }

    /**
     * Get encrypted SSH private key.
     */
    public function getDecryptedSshPrivateKey(): ?string
    {
        return $this->ssh_private_key ? Crypt::decryptString($this->ssh_private_key) : null;
    }

    /**
     * Set encrypted SSH private key.
     */
    public function setSshPrivateKey(string $key): void
    {
        $this->ssh_private_key = Crypt::encryptString($key);
    }

    /**
     * Check if server is online (seen in last 5 minutes).
     */
    public function isOnline(): bool
    {
        return $this->last_seen && $this->last_seen->isAfter(now()->subMinutes(5));
    }

    /**
     * Accessor for is_online attribute.
     */
    public function getIsOnlineAttribute(): bool
    {
        return $this->isOnline();
    }
}
