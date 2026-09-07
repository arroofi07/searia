<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Models\Club;
use App\Models\Competition;
use App\Models\Registration;
use Illuminate\Support\Collection;

class ClubStanding
{
    /**
     * @return Collection<int, array{
     *     club_id: int,
     *     club_name: string,
     *     city: string|null,
     *     gold: int,
     *     silver: int,
     *     bronze: int,
     *     total: int,
     *     participants: int,
     *     has_medals: bool
     * }>
     */
    public function forCompetition(Competition $competition, MedalTally $medals, RankingCalculator $ranking): Collection
    {
        $medalRows = $medals->byClub($competition, $ranking)->keyBy('club_id');

        $participantCounts = Registration::query()
            ->where('competition_id', $competition->id)
            ->where('status', RegistrationStatus::Verified)
            ->join('athletes', 'athletes.id', '=', 'registrations.athlete_id')
            ->selectRaw('athletes.club_id, count(distinct athletes.id) as participant_count')
            ->groupBy('athletes.club_id')
            ->pluck('participant_count', 'club_id');

        $clubIds = $participantCounts->keys()
            ->merge($medalRows->keys())
            ->unique()
            ->values();

        $clubs = Club::query()->whereIn('id', $clubIds)->get()->keyBy('id');

        $rows = $clubIds->map(function (int $clubId) use ($medalRows, $participantCounts, $clubs) {
            $medal = $medalRows->get($clubId);
            $gold = (int) ($medal['gold'] ?? 0);
            $silver = (int) ($medal['silver'] ?? 0);
            $bronze = (int) ($medal['bronze'] ?? 0);

            return [
                'club_id' => $clubId,
                'club_name' => $clubs->get($clubId)?->name ?? ($medal['club_name'] ?? '—'),
                'city' => $clubs->get($clubId)?->city,
                'gold' => $gold,
                'silver' => $silver,
                'bronze' => $bronze,
                'total' => $gold + $silver + $bronze,
                'participants' => (int) ($participantCounts[$clubId] ?? 0),
                'has_medals' => ($gold + $silver + $bronze) > 0,
            ];
        });

        return $rows->sort(function (array $a, array $b): int {
            if ($a['has_medals'] !== $b['has_medals']) {
                return $a['has_medals'] ? -1 : 1;
            }

            return [$b['gold'], $b['silver'], $b['bronze'], $a['club_name']]
                <=> [$a['gold'], $a['silver'], $a['bronze'], $b['club_name']];
        })->values();
    }
}
