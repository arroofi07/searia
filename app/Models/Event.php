<?php

namespace App\Models;

use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\Stroke;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;

    protected $fillable = [
        'competition_id',
        'event_number',
        'gender',
        'distance',
        'stroke',
        'equipment',
        'session',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'event_number' => 'integer',
            'gender' => EventGender::class,
            'distance' => 'integer',
            'stroke' => Stroke::class,
            'equipment' => Equipment::class,
            'session' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Competition, $this>
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * @return BelongsToMany<AgeGroup, $this>
     */
    public function ageGroups(): BelongsToMany
    {
        return $this->belongsToMany(AgeGroup::class, 'event_age_group')->withTimestamps();
    }

    /**
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * @return HasMany<Heat, $this>
     */
    public function heats(): HasMany
    {
        return $this->hasMany(Heat::class);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => $this->formattedName());
    }

    public function formattedName(): string
    {
        $name = $this->distance.' M '.$this->stroke->label();

        if ($this->equipment !== Equipment::None) {
            $name .= ' ('.$this->equipment->label().')';
        }

        return $name.' - '.$this->gender->label();
    }

    public function shortName(): string
    {
        $name = $this->distance.' M '.$this->stroke->label();

        if ($this->equipment !== Equipment::None) {
            $name .= ' ('.$this->equipment->label().')';
        }

        return $name;
    }

    public function isSeeded(): bool
    {
        return $this->heats()->exists();
    }
}
