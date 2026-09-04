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
     * Enam grup baku: Group 1 termuda sampai Group 6 tertua, dihitung dari tahun lomba.
     *
     * @return list<array{code: string, name: string, display_code: string, birth_year_start: int, birth_year_end: int, sort_order: int}>
     */
    public static function defaultDefinitions(int $year): array
    {
        return [
            [
                'code' => '1',
                'name' => 'Group 1',
                'display_code' => 'I',
                'birth_year_start' => $year - 7,
                'birth_year_end' => $year,
                'sort_order' => 1,
            ],
            [
                'code' => '2',
                'name' => 'Group 2',
                'display_code' => 'II',
                'birth_year_start' => $year - 9,
                'birth_year_end' => $year - 8,
                'sort_order' => 2,
            ],
            [
                'code' => '3',
                'name' => 'Group 3',
                'display_code' => 'III',
                'birth_year_start' => $year - 11,
                'birth_year_end' => $year - 10,
                'sort_order' => 3,
            ],
            [
                'code' => '4',
                'name' => 'Group 4',
                'display_code' => 'IV',
                'birth_year_start' => $year - 13,
                'birth_year_end' => $year - 12,
                'sort_order' => 4,
            ],
            [
                'code' => '5',
                'name' => 'Group 5',
                'display_code' => 'V',
                'birth_year_start' => $year - 15,
                'birth_year_end' => $year - 14,
                'sort_order' => 5,
            ],
            [
                'code' => '6',
                'name' => 'Group 6',
                'display_code' => 'VI',
                'birth_year_start' => 1950,
                'birth_year_end' => $year - 16,
                'sort_order' => 6,
            ],
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
