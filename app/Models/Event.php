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
     * 29 nomor lomba baku Fun Swimming SeaRIA (58 nomor acara PA/PI, H1+H2).
     * Kode grup mengikuti matriks brosur Group 1–8. Group 9 (jika ada) ikut
     * nomor yang sama dengan Group 8.
     *
     * @return list<array{distance: int, stroke: Stroke, equipment: Equipment, male_number: int, female_number: int, session: int, eligible_codes: list<string>}>
     */
    public static function defaultProgram(): array
    {
        $eligible = static function (int ...$groups): array {
            $codes = array_map(strval(...), $groups);

            if (in_array(8, $groups, true)) {
                $codes[] = '9';
            }

            return $codes;
        };

        $pairs = [
            [1, 1, 50, Stroke::Butterfly, Equipment::None, $eligible(3, 4, 5)],
            [1, 3, 100, Stroke::Freestyle, Equipment::Fins, $eligible(1, 2, 4)],
            [1, 5, 25, Stroke::Backstroke, Equipment::None, $eligible(6, 7, 8)],
            [1, 7, 50, Stroke::Breaststroke, Equipment::None, $eligible(3, 4, 5, 6)],
            [1, 9, 100, Stroke::Butterfly, Equipment::Fins, $eligible(1, 2)],
            [1, 11, 25, Stroke::Freestyle, Equipment::None, $eligible(6, 7, 8)],
            [1, 13, 200, Stroke::Freestyle, Equipment::None, $eligible(1, 2)],
            [1, 15, 50, Stroke::Butterfly, Equipment::Kickboard, $eligible(4, 5)],
            [1, 17, 100, Stroke::Backstroke, Equipment::None, $eligible(1, 2, 3)],
            [1, 19, 25, Stroke::Butterfly, Equipment::Kickboard, $eligible(5, 6, 7, 8)],
            [1, 21, 50, Stroke::Backstroke, Equipment::Fins, $eligible(3, 4, 5)],
            [1, 23, 50, Stroke::Breaststroke, Equipment::Kickboard, $eligible(4, 5, 6)],
            [1, 25, 25, Stroke::Butterfly, Equipment::None, $eligible(6, 7, 8)],
            [1, 27, 100, Stroke::Freestyle, Equipment::None, $eligible(1, 2, 3, 4, 5)],
            [1, 29, 25, Stroke::Freestyle, Equipment::Kickboard, $eligible(6, 7, 8)],
            [1, 31, 25, Stroke::Breaststroke, Equipment::None, $eligible(6, 7, 8)],
            [2, 33, 50, Stroke::Butterfly, Equipment::Fins, $eligible(3)],
            [2, 35, 100, Stroke::Backstroke, Equipment::Fins, $eligible(1, 2)],
            [2, 37, 25, Stroke::Freestyle, Equipment::Fins, $eligible(6, 7, 8)],
            [2, 39, 100, Stroke::Butterfly, Equipment::None, $eligible(1, 2, 3)],
            [2, 41, 100, Stroke::Breaststroke, Equipment::None, $eligible(1, 2, 3, 4, 5)],
            [2, 43, 25, Stroke::Butterfly, Equipment::Fins, $eligible(4, 5, 6, 7, 8)],
            [2, 45, 200, Stroke::Freestyle, Equipment::Fins, $eligible(1, 2)],
            [2, 47, 50, Stroke::Backstroke, Equipment::None, $eligible(3, 4, 5)],
            [2, 49, 25, Stroke::Backstroke, Equipment::Fins, $eligible(6, 7, 8)],
            [2, 51, 25, Stroke::Breaststroke, Equipment::Kickboard, $eligible(6, 7, 8)],
            [2, 53, 50, Stroke::Freestyle, Equipment::Fins, $eligible(3, 4, 5)],
            [2, 55, 50, Stroke::Freestyle, Equipment::Kickboard, $eligible(4, 5)],
            [2, 57, 50, Stroke::Freestyle, Equipment::None, $eligible(3, 4, 5)],
        ];

        $program = [];

        foreach ($pairs as [$session, $maleNumber, $distance, $stroke, $equipment, $eligibleCodes]) {
            $program[] = [
                'distance' => $distance,
                'stroke' => $stroke,
                'equipment' => $equipment,
                'male_number' => $maleNumber,
                'female_number' => $maleNumber + 1,
                'session' => $session,
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

    public function acceptsAthlete(Athlete $athlete, ?AgeGroup $ageGroup): bool
    {
        if ($athlete->gender->eventGender() !== $this->gender) {
            return false;
        }

        if ($ageGroup === null) {
            return false;
        }

        $this->loadMissing('ageGroups');

        return $this->ageGroups->contains('id', $ageGroup->id);
    }
}
