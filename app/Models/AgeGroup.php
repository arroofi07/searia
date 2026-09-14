<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgeGroup extends Model
{
    /** @use HasFactory<\Database\Factories\AgeGroupFactory> */
    use HasFactory;

    protected $fillable = [
        'competition_id',
        'code',
        'name',
        'display_code',
        'birth_year_start',
        'birth_year_end',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'birth_year_start' => 'integer',
            'birth_year_end' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Sembilan grup baku Fun Swimming SeaRIA: Group 1 tertua sampai Group 9 termuda.
     *
     * @return list<array{code: string, name: string, display_code: string, birth_year_start: int, birth_year_end: int, sort_order: int}>
     */
    public static function defaultDefinitions(int $year): array
    {
        $romans = ['1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV', '5' => 'V', '6' => 'VI', '7' => 'VII', '8' => 'VIII', '9' => 'IX'];
        $ranges = [
            '1' => [1950, $year - 15],
            '2' => [$year - 14, $year - 13],
            '3' => [$year - 12, $year - 12],
            '4' => [$year - 11, $year - 11],
            '5' => [$year - 10, $year - 10],
            '6' => [$year - 9, $year - 9],
            '7' => [$year - 8, $year - 8],
            '8' => [$year - 7, $year - 7],
            '9' => [$year - 6, $year],
        ];

        $definitions = [];

        foreach ($ranges as $code => [$start, $end]) {
            $code = (string) $code;
            $definitions[] = [
                'code' => $code,
                'name' => 'Group '.$code,
                'display_code' => $romans[$code],
                'birth_year_start' => $start,
                'birth_year_end' => $end,
                'sort_order' => (int) $code,
            ];
        }

        return $definitions;
    }

    /**
     * @return BelongsTo<Competition, $this>
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * @return BelongsToMany<Event, $this>
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_age_group')->withTimestamps();
    }

    /**
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * @param  Builder<AgeGroup>  $query
     * @return Builder<AgeGroup>
     */
    public function scopeContainingBirthYear(Builder $query, int $year): Builder
    {
        return $query
            ->where('birth_year_start', '<=', $year)
            ->where('birth_year_end', '>=', $year);
    }

    public function containsBirthYear(int $year): bool
    {
        return $this->birth_year_start <= $year && $year <= $this->birth_year_end;
    }

    public function overlaps(int $start, int $end): bool
    {
        return max($this->birth_year_start, $start) <= min($this->birth_year_end, $end);
    }
}
