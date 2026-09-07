<?php

namespace App\Services\Seeding;

use App\Models\Registration;
use Illuminate\Support\Collection;

class EntrantSorter
{
    /**
     * @param  Collection<int, Registration>  $registrations
     * @return Collection<int, Registration>
     */
    public function sort(Collection $registrations, int $competitionId, int $eventId, int $ageGroupId): Collection
    {
        $timed = $registrations
            ->filter(fn (Registration $registration): bool => $registration->seed_time_ms !== null)
            ->sort(function (Registration $left, Registration $right): int {
                $timeCompare = $left->seed_time_ms <=> $right->seed_time_ms;

                if ($timeCompare !== 0) {
                    return $timeCompare;
                }

                $clubCompare = strcmp(
                    (string) $left->athlete?->club?->name,
                    (string) $right->athlete?->club?->name,
                );

                if ($clubCompare !== 0) {
                    return $clubCompare;
                }

                return strcmp(
                    (string) $left->athlete?->full_name,
                    (string) $right->athlete?->full_name,
                );
            })
            ->values();

        $nt = $registrations
            ->filter(fn (Registration $registration): bool => $registration->seed_time_ms === null)
            ->sortBy('id')
            ->values();

        $nt = $this->shuffleWithSeed($nt, $competitionId, $eventId, $ageGroupId);

        return $timed->concat($nt)->values();
    }

    /**
     * @param  Collection<int, Registration>  $registrations
     * @return Collection<int, Registration>
     */
    private function shuffleWithSeed(Collection $registrations, int $competitionId, int $eventId, int $ageGroupId): Collection
    {
        $items = $registrations->all();
        $count = count($items);

        if ($count <= 1) {
            return collect($items);
        }

        $seed = crc32($competitionId.'|'.$eventId.'|'.$ageGroupId);

        for ($i = $count - 1; $i > 0; $i--) {
            $seed = ($seed * 1103515245 + 12345) & 0x7FFFFFFF;
            $j = $seed % ($i + 1);
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }

        return collect($items)->values();
    }
}
