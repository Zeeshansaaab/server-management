<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SslCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'domain',
        'status',
        'issued_at',
        'expires_at',
        'certificate_path',
        'private_key_path',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the site that owns the certificate.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Check if certificate is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at && $this->expires_at->isFuture();
    }

    /**
     * Check if certificate is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
