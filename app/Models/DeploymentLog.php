<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeploymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'deployment_id',
        'step',
        'output',
        'level',
        'order',
    ];

    /**
     * Get the deployment that owns the log.
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }
}
