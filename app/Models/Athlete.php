<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Athlete extends Model
{
    /** @use HasFactory<\Database\Factories\AthleteFactory> */
    use HasFactory;

    protected $fillable = [
        'club_id',
        'full_name',
        'gender',
        'birth_year',
        'birth_date',
        'identity_number',
        'photo_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'birth_year' => 'integer',
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Club, $this>
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * @param  Builder<Athlete>  $query
     * @return Builder<Athlete>
     */
    public function scopeByBirthYearRange(Builder $query, int $from, int $to): Builder
    {
        return $query->whereBetween('birth_year', [min($from, $to), max($from, $to)]);
    }
}
