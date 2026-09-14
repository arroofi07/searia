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
     * @return BelongsToMany<User, $this>
     */
    public function judges(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_judge')->withTimestamps();
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

    /**
     * Nama resmi seperti di cetakan susunan acara (huruf besar, tanpa label gender).
     */
    public function programName(): string
    {
        if ($this->stroke === Stroke::Freestyle && $this->equipment === Equipment::Fins) {
            return $this->distance.' M BEBAS (FINS)';
        }

        $stroke = match ($this->stroke) {
            Stroke::Butterfly => 'GAYA KUPU-KUPU',
            Stroke::Backstroke => 'GAYA PUNGGUNG',
            Stroke::Breaststroke => 'GAYA DADA',
            Stroke::Freestyle => 'GAYA BEBAS',
            Stroke::Medley => 'GAYA GANTI',
        };

        $name = $this->distance.' M '.$stroke;

        if ($this->equipment === Equipment::Fins) {
            $name .= ' (FINS)';
        } elseif ($this->equipment === Equipment::Kickboard) {
            $name .= ' (KICKBOARD)';
        }

        return $name;
    }

    /**
     * 18 nomor lomba baku Fun Swimming SeaRIA (36 nomor acara PA/PI).
     *
     * @return list<array{distance: int, stroke: Stroke, equipment: Equipment, male_number: int, female_number: int, eligible_codes: list<string>}>
     */
    public static function defaultProgram(): array
    {
        $pairs = [
            [50, Stroke::Butterfly, Equipment::None, ['2', '3', '4', '5']],
            [50, Stroke::Backstroke, Equipment::None, ['2', '3', '4', '5']],
            [50, Stroke::Butterfly, Equipment::Fins, ['4', '5', '6', '7']],
            [50, Stroke::Backstroke, Equipment::Fins, ['4', '5', '6', '7']],
            [25, Stroke::Butterfly, Equipment::None, ['5', '6', '7']],
            [25, Stroke::Backstroke, Equipment::None, ['5', '6', '7']],
            [50, Stroke::Breaststroke, Equipment::None, ['2', '3', '4', '5']],
            [50, Stroke::Freestyle, Equipment::None, ['2', '3', '4', '5']],
            [50, Stroke::Butterfly, Equipment::Kickboard, ['4', '5']],
            [50, Stroke::Breaststroke, Equipment::Kickboard, ['4', '5']],
            [200, Stroke::Freestyle, Equipment::None, ['1']],
            [25, Stroke::Breaststroke, Equipment::None, ['5', '6', '7', '8', '9']],
            [25, Stroke::Freestyle, Equipment::None, ['5', '6', '7', '8', '9']],
            [25, Stroke::Butterfly, Equipment::Kickboard, ['6', '7']],
            [25, Stroke::Breaststroke, Equipment::Kickboard, ['6', '7', '8', '9']],
            [50, Stroke::Freestyle, Equipment::Kickboard, ['4', '5']],
            [25, Stroke::Freestyle, Equipment::Kickboard, ['6', '7', '8', '9']],
            [50, Stroke::Freestyle, Equipment::Fins, ['2', '3', '4', '5', '6', '7']],
        ];

        $program = [];

        foreach ($pairs as $index => [$distance, $stroke, $equipment, $eligibleCodes]) {
            $maleNumber = ($index * 2) + 1;
            $program[] = [
                'distance' => $distance,
                'stroke' => $stroke,
                'equipment' => $equipment,
                'male_number' => $maleNumber,
                'female_number' => $maleNumber + 1,
                'eligible_codes' => $eligibleCodes,
            ];
        }

        return $program;
    }

    /**
     * @return list<string>
     */
    public static function defaultEligibleGroupCodes(int $distance, Stroke $stroke, Equipment $equipment): array
    {
        foreach (self::defaultProgram() as $pair) {
            if ($pair['distance'] === $distance && $pair['stroke'] === $stroke && $pair['equipment'] === $equipment) {
                return $pair['eligible_codes'];
            }
        }

        return [];
    }

    public function paddedEventNumber(): string
    {
        return str_pad((string) $this->event_number, 2, '0', STR_PAD_LEFT);
    }

    public function isSeeded(): bool
    {
        return $this->heats()->exists();
    }
}
