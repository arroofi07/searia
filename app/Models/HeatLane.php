<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HeatLane extends Model
{
    /** @use HasFactory<\Database\Factories\HeatLaneFactory> */
    use HasFactory;

    protected $fillable = [
        'heat_id',
        'lane_number',
        'registration_id',
    ];

    protected function casts(): array
    {
        return [
            'lane_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Heat, $this>
     */
    public function heat(): BelongsTo
    {
        return $this->belongsTo(Heat::class);
    }

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * @return HasOne<Result, $this>
     */
    public function result(): HasOne
    {
        return $this->hasOne(Result::class);
    }
}
