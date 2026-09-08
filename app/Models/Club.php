<?php

namespace App\Models;

use App\Enums\ClubStatus;
use App\Enums\ClubType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Club extends Model
{
    /** @use HasFactory<\Database\Factories\ClubFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'type',
        'city',
        'province',
        'contact_name',
        'contact_phone',
        'logo_path',
        'status',
        'rejection_reason',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => ClubType::class,
            'status' => ClubStatus::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Athlete, $this>
     */
    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isVerified(): bool
    {
        return $this->status === ClubStatus::Verified;
    }
}
