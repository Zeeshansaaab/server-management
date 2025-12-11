<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledCommand extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'command',
        'cron_expression',
        'user',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the site that owns the command.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
