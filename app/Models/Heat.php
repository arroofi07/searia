<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Heat extends Model
{
    /** @use HasFactory<\Database\Factories\HeatFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'age_group_id',
        'heat_number',
        'round',
        'scheduled_at',
        'status',
        'locked_at',
        'seeded_at',
    ];

    protected function casts(): array
    {
        return [
            'heat_number' => 'integer',
            'scheduled_at' => 'datetime',
            'locked_at' => 'datetime',
            'seeded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<AgeGroup, $this>
     */
    public function ageGroup(): BelongsTo
    {
        return $this->belongsTo(AgeGroup::class);
    }

    /**
     * @return HasMany<HeatLane, $this>
     */
    public function lanes(): HasMany
    {
        return $this->hasMany(HeatLane::class);
    }

    /**
     * @return HasManyThrough<Result, HeatLane, $this>
     */
    public function results(): HasManyThrough
    {
        return $this->hasManyThrough(Result::class, HeatLane::class);
    }
}
