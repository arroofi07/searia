<?php

namespace App\Services;

use App\DataTransferObjects\RankingEntry;
use App\DataTransferObjects\RankingTable;
use App\Enums\ResultStatus;
use App\Models\AgeGroup;
use App\Models\Event;
use App\Models\Result;
use Illuminate\Support\Collection;

class RankingCalculator
{
    public function forEventAgeGroup(Event $event, AgeGroup $ageGroup): RankingTable
    {
        $results = Result::query()
            ->with([
                'heatLane.heat',
                'heatLane.registration.athlete.club',
            ])
            ->whereHas('heatLane.heat', function ($query) use ($event, $ageGroup): void {
                $query->where('event_id', $event->id)
                    ->where('age_group_id', $ageGroup->id);
            })
            ->get();

        return new RankingTable(
            eventId: $event->id,
            ageGroupId: $ageGroup->id,
            eventTitle: 'Acara '.$event->event_number.' - '.$event->formattedName(),
            ageGroupName: $ageGroup->name,
            entries: $this->rankResults($results)->all(),
        );
    }

    /**
     * @param  Collection<int, Result>  $results
     * @return Collection<int, RankingEntry>
     */
    public function rankResults(Collection $results): Collection
    {
        $ok = $results
            ->filter(fn (Result $result): bool => $result->status === ResultStatus::Ok)
            ->sort(fn (Result $a, Result $b): int => [$a->time_ms ?? PHP_INT_MAX, $a->id] <=> [$b->time_ms ?? PHP_INT_MAX, $b->id])
            ->values();

        $others = $results
            ->filter(fn (Result $result): bool => $result->status !== ResultStatus::Ok)
            ->sort(fn (Result $a, Result $b): int => [
                $a->status->value,
                $a->heatLane?->heat?->heat_number ?? 0,
                $a->heatLane?->lane_number ?? 0,
            ] <=> [
                $b->status->value,
                $b->heatLane?->heat?->heat_number ?? 0,
                $b->heatLane?->lane_number ?? 0,
            ])
            ->values();

        $firstTime = $ok->first()?->time_ms;
        $ranked = collect();
        $position = 0;
        $lastTime = null;
        $currentRank = 0;

        foreach ($ok as $result) {
            $position++;
            if ($lastTime === null || $result->time_ms !== $lastTime) {
                $currentRank = $position;
                $lastTime = $result->time_ms;
            }

            $ranked->push($this->toEntry($result, $currentRank, $firstTime));
        }

        foreach ($others as $result) {
            $ranked->push($this->toEntry($result, null, $firstTime));
        }

        return $ranked;
    }

    private function toEntry(Result $result, ?int $rank, ?int $firstTimeMs): RankingEntry
    {
        $lane = $result->heatLane;
        $registration = $lane?->registration;
        $athlete = $registration?->athlete;
        $club = $athlete?->club;
        $seed = $registration?->seed_time_ms;
        $time = $result->time_ms;

        $isPb = $result->status === ResultStatus::Ok
            && $time !== null
            && ($seed === null || $time < $seed);

        $gap = ($rank !== null && $firstTimeMs !== null && $time !== null)
            ? $time - $firstTimeMs
            : null;

        return new RankingEntry(
            resultId: $result->id,
            rank: $rank,
            athleteName: (string) ($athlete?->full_name ?? '—'),
            athleteId: (int) ($athlete?->id ?? 0),
            clubName: (string) ($club?->name ?? '—'),
            clubId: $club?->id,
            city: $club?->city,
            status: $result->status,
            timeMs: $time,
            seedTimeMs: $seed,
            heatNumber: (int) ($lane?->heat?->heat_number ?? 0),
            laneNumber: (int) ($lane?->lane_number ?? 0),
            gapToFirstMs: $gap,
            isPersonalBest: $isPb,
            dsqCode: $result->dsq_code?->value,
        );
    }
}
