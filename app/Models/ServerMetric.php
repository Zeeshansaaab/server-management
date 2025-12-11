<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'cpu_usage',
        'memory_used_mb',
        'memory_total_mb',
        'disk_used_gb',
        'disk_total_gb',
        'load_average_1m',
        'load_average_5m',
        'load_average_15m',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * Get the server that owns the metric.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
