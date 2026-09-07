<?php

namespace App\Services;

use App\DataTransferObjects\RankingEntry;
use App\Models\AgeGroup;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Support\Collection;

class MedalTally
{
    /**
     * @return Collection<int, array{
     *     event: Event,
     *     age_group: AgeGroup,
     *     entries: list<RankingEntry>,
     *     gold: int,
     *     silver: int,
     *     bronze: int,
     *     small_field: bool,
     *     medals: list<array{metal: string, entry: RankingEntry}>
     * }>
     */
    public function forCompetition(Competition $competition, RankingCalculator $ranking): Collection
    {
        $blocks = collect();

        foreach ($competition->events as $event) {
            $ageGroupIds = $event->heats()->distinct()->pluck('age_group_id');
            foreach ($ageGroupIds as $ageGroupId) {
                $ageGroup = AgeGroup::query()->find($ageGroupId);
                if ($ageGroup === null) {
                    continue;
                }

                $table = $ranking->forEventAgeGroup($event, $ageGroup);
                $participantCount = count($table->entries);
                $medals = [];
                $gold = $silver = $bronze = 0;

                foreach ($table->entries as $entry) {
                    if ($entry->rank === 1) {
                        $medals[] = ['metal' => 'gold', 'entry' => $entry];
                        $gold++;
                    } elseif ($entry->rank === 2) {
                        $medals[] = ['metal' => 'silver', 'entry' => $entry];
                        $silver++;
                    } elseif ($entry->rank === 3) {
                        $medals[] = ['metal' => 'bronze', 'entry' => $entry];
                        $bronze++;
                    }
                }

                $blocks->push([
                    'event' => $event,
                    'age_group' => $ageGroup,
                    'entries' => $table->entries,
                    'gold' => $gold,
                    'silver' => $silver,
                    'bronze' => $bronze,
                    'small_field' => $participantCount > 0 && $participantCount < 3,
                    'medals' => $medals,
                ]);
            }
        }

        return $blocks;
    }

    /**
     * @return Collection<int, array{club_id: int, club_name: string, gold: int, silver: int, bronze: int, small_fields: int}>
     */
    public function byClub(Competition $competition, RankingCalculator $ranking): Collection
    {
        $totals = [];

        foreach ($this->forCompetition($competition, $ranking) as $block) {
            foreach ($block['medals'] as $medal) {
                /** @var RankingEntry $entry */
                $entry = $medal['entry'];
                if ($entry->clubId === null) {
                    continue;
                }
                $id = $entry->clubId;
                $totals[$id] ??= [
                    'club_id' => $id,
                    'club_name' => $entry->clubName,
                    'gold' => 0,
                    'silver' => 0,
                    'bronze' => 0,
                    'small_fields' => 0,
                ];
                $totals[$id][$medal['metal']]++;
            }
            if ($block['small_field']) {
                // tracked at block level for reports; club rollup of small fields optional
            }
        }

        return collect($totals)->values();
    }
}
