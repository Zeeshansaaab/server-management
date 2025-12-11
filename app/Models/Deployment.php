<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deployment extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'status',
        'release_path',
        'commit_hash',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the site that owns the deployment.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the logs for the deployment.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(DeploymentLog::class)->orderBy('order');
    }

    /**
     * Check if deployment is running.
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if deployment succeeded.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if deployment failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
