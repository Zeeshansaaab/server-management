<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'database_id',
        'type',
        'status',
        'file_path',
        'file_size',
        'completed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the site that owns the backup.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the database that owns the backup.
     */
    public function database(): BelongsTo
    {
        return $this->belongsTo(Database::class);
    }
}
