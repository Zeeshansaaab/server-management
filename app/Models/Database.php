<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Database extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'site_id',
        'name',
        'username',
        'password',
        'host',
        'port',
    ];

    /**
     * Get the server that owns the database.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Get the site that uses the database.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get decrypted password.
     */
    public function getDecryptedPassword(): string
    {
        return Crypt::decryptString($this->password);
    }

    /**
     * Set encrypted password.
     */
    public function setPassword(string $password): void
    {
        $this->password = Crypt::encryptString($password);
    }
}
