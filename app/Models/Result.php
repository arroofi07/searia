<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    /** @use HasFactory<\Database\Factories\ResultFactory> */
    use HasFactory;

    protected $fillable = [
        'heat_lane_id',
        'time_ms',
        'status',
        'dsq_code',
        'dsq_reason',
        'recorded_by',
        'recorded_at',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'time_ms' => 'integer',
            'recorded_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<HeatLane, $this>
     */
    public function heatLane(): BelongsTo
    {
        return $this->belongsTo(HeatLane::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
