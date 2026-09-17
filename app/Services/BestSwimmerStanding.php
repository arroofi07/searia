<?php

namespace App\Services;

use App\Enums\Gender;
use App\Models\Athlete;
use App\Models\Competition;
use Illuminate\Support\Collection;

class BestSwimmerStanding
{
    public const GOLD_POINTS = 5;

    public const SILVER_POINTS = 3;

    public const BRONZE_POINTS = 1;

    /**
     * @return Collection<int, array{
     *     gender: Gender,
     *     gender_label: string,
     *     age_group_id: int,
     *     age_group_name: string,
     *     age_group_code: string,
     *     sort_order: int,
     *     athletes: list<array{
     *         position: int,
     *         athlete_id: int,
     *         identity_number: string|null,
     *         athlete_name: string,
     *         gender_label: string,
     *         age_group_name: string,
     *         club_name: string,
     *         city: string|null,
     *         gold: int,
     *         silver: int,
     *         bronze: int,
     *         total: int,
     *         points: int
     *     }>,
     *     winners: list<array{
     *         position: int,
     *         athlete_id: int,
     *         identity_number: string|null,
     *         athlete_name: string,
     *         gender_label: string,
     *         age_group_name: string,
     *         club_name: string,
     *         city: string|null,
     *         gold: int,
     *         silver: int,
     *         bronze: int,
     *         total: int,
     *         points: int
     *     }>
     * }>
     */
    public function forCompetition(Competition $competition, MedalTally $medals, RankingCalculator $ranking): Collection
    {
        $totals = [];

        foreach ($medals->forCompetition($competition, $ranking) as $block) {
            $ageGroup = $block['age_group'];

            foreach ($block['medals'] as $medal) {
                $entry = $medal['entry'];
                if ($entry->athleteId <= 0) {
                    continue;
                }

                $key = $entry->athleteId.'-'.$ageGroup->id;
                $totals[$key] ??= [
                    'athlete_id' => $entry->athleteId,
                    'age_group_id' => $ageGroup->id,
                    'age_group_name' => $ageGroup->name,
                    'age_group_code' => $ageGroup->display_code ?: $ageGroup->code,
                    'sort_order' => (int) $ageGroup->sort_order,
                    'club_name' => $entry->clubName,
                    'city' => $entry->city,
                    'gold' => 0,
                    'silver' => 0,
                    'bronze' => 0,
                ];
                $totals[$key][$medal['metal']]++;
            }
        }

        if ($totals === []) {
            return collect();
        }

        $athletes = Athlete::query()
            ->whereIn('id', collect($totals)->pluck('athlete_id')->unique()->all())
            ->get()
            ->keyBy('id');

        $grouped = [];

        foreach ($totals as $row) {
            $athlete = $athletes->get($row['athlete_id']);
            if ($athlete === null) {
                continue;
            }

            $gender = $athlete->gender;
            $genderLabel = $gender->eventGender()->label();
            $gold = (int) $row['gold'];
            $silver = (int) $row['silver'];
            $bronze = (int) $row['bronze'];
            $groupKey = $gender->value.'-'.$row['age_group_id'];

            $grouped[$groupKey] ??= [
                'gender' => $gender,
                'gender_label' => $genderLabel,
                'age_group_id' => $row['age_group_id'],
                'age_group_name' => $row['age_group_name'],
                'age_group_code' => $row['age_group_code'],
                'sort_order' => $row['sort_order'],
                'athletes' => [],
            ];

            $grouped[$groupKey]['athletes'][] = [
                'position' => 0,
                'athlete_id' => $athlete->id,
                'identity_number' => $athlete->identity_number,
                'athlete_name' => $athlete->full_name,
                'gender_label' => $genderLabel,
                'age_group_name' => $row['age_group_name'],
                'club_name' => $row['club_name'],
                'city' => $row['city'],
                'gold' => $gold,
                'silver' => $silver,
                'bronze' => $bronze,
                'total' => $gold + $silver + $bronze,
                'points' => ($gold * self::GOLD_POINTS) + ($silver * self::SILVER_POINTS) + ($bronze * self::BRONZE_POINTS),
            ];
        }

        return collect($grouped)
            ->map(function (array $group): array {
                $sorted = collect($group['athletes'])
                    ->sort(fn (array $a, array $b): int => [$b['gold'], $b['silver'], $b['bronze'], $a['athlete_name']]
                        <=> [$a['gold'], $a['silver'], $a['bronze'], $b['athlete_name']])
                    ->values();

                $position = 0;
                $currentRank = 0;
                $lastKey = null;
                $ranked = [];

                foreach ($sorted as $athlete) {
                    $position++;
                    $medalKey = $athlete['gold'].'-'.$athlete['silver'].'-'.$athlete['bronze'];
                    if ($medalKey !== $lastKey) {
                        $currentRank = $position;
                        $lastKey = $medalKey;
                    }
                    $athlete['position'] = $currentRank;
                    $ranked[] = $athlete;
                }

                $group['athletes'] = $ranked;
                $group['winners'] = array_values(array_filter(
                    $ranked,
                    fn (array $athlete): bool => $athlete['position'] === 1,
                ));

                return $group;
            })
            ->sort(function (array $a, array $b): int {
                return [$this->genderOrder($a['gender']), $a['sort_order'], $a['age_group_name']]
                    <=> [$this->genderOrder($b['gender']), $b['sort_order'], $b['age_group_name']];
            })
            ->values();
    }

    private function genderOrder(Gender $gender): int
    {
        return match ($gender) {
            Gender::Male => 0,
            Gender::Female => 1,
        };
    }
}
