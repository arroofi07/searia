<?php

namespace App\Services;

use App\Exceptions\OverlappingAgeGroupsException;
use App\Models\AgeGroup;
use App\Models\Competition;
use Illuminate\Support\Collection;

class AgeGroupResolver
{
    /** @var array<int, Collection<int, AgeGroup>> */
    private array $cache = [];

    public function resolve(Competition $competition, int $birthYear): ?AgeGroup
    {
        $matches = $this->groupsFor($competition)
            ->filter(fn (AgeGroup $group): bool => $group->containsBirthYear($birthYear))
            ->values();

        if ($matches->count() > 1) {
            throw new OverlappingAgeGroupsException(
                'Lebih dari satu kelompok umur cocok untuk tahun lahir '.$birthYear.'.',
            );
        }

        return $matches->first();
    }

    public function flush(?int $competitionId = null): void
    {
        if ($competitionId === null) {
            $this->cache = [];

            return;
        }

        unset($this->cache[$competitionId]);
    }

    /**
     * @return Collection<int, AgeGroup>
     */
    public function groupsFor(Competition $competition): Collection
    {
        return $this->cache[$competition->id] ??= $competition->ageGroups()->get();
    }
}
