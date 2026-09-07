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
     * @return Collection<int, array{club_id: int, club_name: string, gold: int, silver: int, bronze: int, total: int, small_fields: int}>
     */
    public function byClub(Competition $competition, RankingCalculator $ranking): Collection
    {
        return $this->rollupClubs($this->forCompetition($competition, $ranking));
    }

    /**
     * @param  Collection<int, array{medals: list<array{metal: string, entry: RankingEntry}>}>  $blocks
     * @return Collection<int, array{club_id: int, club_name: string, gold: int, silver: int, bronze: int, total: int, small_fields: int}>
     */
    public function rollupClubs(Collection $blocks): Collection
    {
        $totals = [];

        foreach ($blocks as $block) {
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
        }

        return collect($totals)
            ->map(function (array $row): array {
                $row['total'] = $row['gold'] + $row['silver'] + $row['bronze'];

                return $row;
            })
            ->sort(fn (array $a, array $b): int => [$b['gold'], $b['silver'], $b['bronze'], $a['club_name']]
                <=> [$a['gold'], $a['silver'], $a['bronze'], $b['club_name']])
            ->values();
    }

    /**
     * @return Collection<int, array{age_group_id: int, age_group_name: string, gold: int, silver: int, bronze: int, total: int, small_fields: int}>
     */
    public function byAgeGroup(Competition $competition, RankingCalculator $ranking): Collection
    {
        return $this->rollupAgeGroups($this->forCompetition($competition, $ranking));
    }

    /**
     * @param  Collection<int, array{age_group: AgeGroup, gold: int, silver: int, bronze: int, small_field: bool}>  $blocks
     * @return Collection<int, array{age_group_id: int, age_group_name: string, gold: int, silver: int, bronze: int, total: int, small_fields: int}>
     */
    public function rollupAgeGroups(Collection $blocks): Collection
    {
        $totals = [];

        foreach ($blocks as $block) {
            $id = $block['age_group']->id;
            $totals[$id] ??= [
                'age_group_id' => $id,
                'age_group_name' => $block['age_group']->name,
                'gold' => 0,
                'silver' => 0,
                'bronze' => 0,
                'small_fields' => 0,
            ];
            $totals[$id]['gold'] += $block['gold'];
            $totals[$id]['silver'] += $block['silver'];
            $totals[$id]['bronze'] += $block['bronze'];
            if ($block['small_field']) {
                $totals[$id]['small_fields']++;
            }
        }

        return collect($totals)
            ->map(function (array $row): array {
                $row['total'] = $row['gold'] + $row['silver'] + $row['bronze'];

                return $row;
            })
            ->sortBy('age_group_name')
            ->values();
    }
}
