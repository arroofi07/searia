<?php

namespace App\Rules;

use App\Models\AgeGroup;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NonOverlappingBirthYearRange implements ValidationRule
{
    public function __construct(
        private readonly int $competitionId,
        private readonly int $birthYearStart,
        private readonly ?int $ignoreAgeGroupId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $end = (int) $value;

        if ($this->birthYearStart > $end) {
            $fail('Tahun lahir awal tidak boleh lebih besar daripada tahun lahir akhir.');

            return;
        }

        $overlaps = AgeGroup::query()
            ->where('competition_id', $this->competitionId)
            ->when($this->ignoreAgeGroupId !== null, fn ($query) => $query->where('id', '!=', $this->ignoreAgeGroupId))
            ->get()
            ->first(fn (AgeGroup $group): bool => $group->overlaps($this->birthYearStart, $end));

        if ($overlaps instanceof AgeGroup) {
            $fail("Rentang tahun lahir tumpang tindih dengan {$overlaps->name} ({$overlaps->birth_year_start}–{$overlaps->birth_year_end}).");
        }
    }
}
