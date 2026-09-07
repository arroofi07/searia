<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CertificateArchive extends Model
{
    protected $fillable = [
        'competition_id',
        'requested_by',
        'club_id',
        'token',
        'disk_path',
        'status',
        'expires_at',
        'ready_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'ready_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CertificateArchive $archive): void {
            if (blank($archive->token)) {
                $archive->token = Str::random(48);
            }
        });
    }

    /**
     * @return BelongsTo<Competition, $this>
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<Club, $this>
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && $this->disk_path !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
