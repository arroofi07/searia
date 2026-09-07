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
        'results_locked_at',
        'seeded_at',
    ];

    protected function casts(): array
    {
        return [
            'heat_number' => 'integer',
            'scheduled_at' => 'datetime',
            'locked_at' => 'datetime',
            'results_locked_at' => 'datetime',
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

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function isResultsLocked(): bool
    {
        return $this->results_locked_at !== null;
    }

    public function lock(): void
    {
        $this->update(['locked_at' => now()]);
    }

    public function lockResults(): void
    {
        $this->update([
            'results_locked_at' => now(),
            'status' => 'finished',
        ]);
    }

    public function unlockResults(): void
    {
        $this->update([
            'results_locked_at' => null,
            'status' => 'running',
        ]);
    }

    public function occupiedLaneCount(): int
    {
        return $this->lanes()->whereNotNull('registration_id')->count();
    }

    public function recordedResultCount(): int
    {
        return $this->results()->count();
    }

    public function isFullyRecorded(): bool
    {
        $occupied = $this->occupiedLaneCount();

        return $occupied > 0 && $this->recordedResultCount() >= $occupied;
    }
}
