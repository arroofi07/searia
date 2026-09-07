<?php

namespace App\Models;

use App\Enums\CompetitionStatus;
use App\Enums\CompetitionType;
use App\Enums\SeedingMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Competition extends Model
{
    /** @use HasFactory<\Database\Factories\CompetitionFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'venue',
        'city',
        'start_date',
        'end_date',
        'registration_opens_at',
        'registration_closes_at',
        'technical_meeting_at',
        'type',
        'pool_lanes',
        'pool_length',
        'max_events_per_athlete',
        'seeding_mode',
        'fee_per_event',
        'late_fee_per_event',
        'status',
        'banner_path',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'technical_meeting_at' => 'datetime',
            'type' => CompetitionType::class,
            'pool_lanes' => 'integer',
            'pool_length' => 'integer',
            'max_events_per_athlete' => 'integer',
            'seeding_mode' => SeedingMode::class,
            'fee_per_event' => 'integer',
            'late_fee_per_event' => 'integer',
            'status' => CompetitionStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Competition $competition): void {
            if (blank($competition->slug)) {
                $competition->slug = static::uniqueSlugFromName($competition->name);
            }
        });
    }

    public static function uniqueSlugFromName(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'kejuaraan';
        }

        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  Builder<Competition>  $query
     * @return Builder<Competition>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', CompetitionStatus::Published);
    }

    /**
     * @return HasMany<AgeGroup, $this>
     */
    public function ageGroups(): HasMany
    {
        return $this->hasMany(AgeGroup::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class)
            ->orderBy('session')
            ->orderBy('sort_order')
            ->orderBy('event_number');
    }

    /**
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * @return HasMany<ImportBatch, $this>
     */
    public function importBatches(): HasMany
    {
        return $this->hasMany(ImportBatch::class);
    }

    public function isDraft(): bool
    {
        return $this->status === CompetitionStatus::Draft;
    }

    public function isOpenForRegistration(): bool
    {
        return $this->status === CompetitionStatus::Registration;
    }

    public function year(): int
    {
        return (int) $this->start_date->year;
    }
}
