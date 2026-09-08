<?php

namespace App\Services;

use App\DataTransferObjects\ResultsBookAgeGroupBlock;
use App\DataTransferObjects\ResultsBookDocument;
use App\DataTransferObjects\ResultsBookEventBlock;
use App\DataTransferObjects\ResultsBookHeat;
use App\DataTransferObjects\ResultsBookLane;
use App\DataTransferObjects\ResultsBookSession;
use App\Models\AgeGroup;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Result;
use Illuminate\Support\Collection;

class ResultsBookBuilder
{
    public function __construct(
        private readonly RankingCalculator $ranking,
    ) {}

    public function build(
        Competition $competition,
        ?int $session = null,
        ?int $eventId = null,
    ): ResultsBookDocument {
        $eventsQuery = $competition->events()
            ->with([
                'heats' => fn ($query) => $query
                    ->where('round', 'final')
                    ->orderBy('heat_number')
                    ->with([
                        'ageGroup',
                        'lanes.result',
                        'lanes.registration.athlete.club',
                    ]),
            ])
            ->orderBy('session')
            ->orderBy('sort_order')
            ->orderBy('event_number');

        if ($session !== null) {
            $eventsQuery->where('session', $session);
        }

        if ($eventId !== null) {
            $eventsQuery->whereKey($eventId);
        }

        $events = $eventsQuery->get();
        $laneCount = $competition->pool_lanes;
        $sessions = [];

        foreach ($events->groupBy(fn (Event $event): int => (int) $event->session) as $sessionNumber => $sessionEvents) {
            $eventBlocks = [];

            foreach ($sessionEvents as $event) {
                $grouped = $event->heats
                    ->groupBy('age_group_id')
                    ->sortBy(fn (Collection $groupHeats): int => (int) ($groupHeats->first()?->ageGroup?->sort_order ?? 0));

                $ageGroupBlocks = [];

                foreach ($grouped as $groupHeats) {
                    /** @var Heat $sample */
                    $sample = $groupHeats->first();
                    $ageGroup = $sample->ageGroup;
                    if ($ageGroup === null) {
                        continue;
                    }

                    $rankByRegistrationId = $this->ranksFor($event, $ageGroup);
                    $heatBlocks = [];

                    foreach ($groupHeats->sortBy('heat_number') as $heat) {
                        $lanesByNumber = $heat->lanes->keyBy('lane_number');
                        $lanes = [];

                        for ($laneNumber = 1; $laneNumber <= $laneCount; $laneNumber++) {
                            /** @var HeatLane|null $lane */
                            $lane = $lanesByNumber->get($laneNumber);
                            $registration = $lane?->registration;
                            $athlete = $registration?->athlete;
                            $club = $athlete?->club;
                            $result = $lane?->result;

                            $lanes[] = new ResultsBookLane(
                                laneNumber: $laneNumber,
                                registrationId: $registration?->id,
                                athleteName: $athlete?->full_name,
                                birthYear: $athlete?->birth_year,
                                ageGroupCode: $ageGroup->display_code ?: $ageGroup->code,
                                clubName: $club?->name,
                                city: $club?->city,
                                resultTimeMs: $result?->time_ms,
                                resultStatus: $result?->status,
                                rank: $registration ? ($rankByRegistrationId[$registration->id] ?? null) : null,
                                dsqCode: $result?->dsq_code?->value ?? (is_string($result?->dsq_code) ? $result->dsq_code : null),
                            );
                        }

                        $heatBlocks[] = new ResultsBookHeat(
                            heatId: $heat->id,
                            heatNumber: $heat->heat_number,
                            lanes: $lanes,
                        );
                    }

                    if ($heatBlocks === []) {
                        continue;
                    }

                    $ageGroupBlocks[] = new ResultsBookAgeGroupBlock(
                        ageGroupId: $ageGroup->id,
                        name: $ageGroup->name,
                        displayCode: $ageGroup->display_code ?: $ageGroup->code,
                        sortOrder: $ageGroup->sort_order,
                        heats: $heatBlocks,
                    );
                }

                if ($ageGroupBlocks === []) {
                    continue;
                }

                $eventBlocks[] = new ResultsBookEventBlock(
                    eventId: $event->id,
                    eventNumber: $event->event_number,
                    eventName: $event->formattedName(),
                    session: (int) $event->session,
                    sortOrder: (int) $event->sort_order,
                    ageGroups: $ageGroupBlocks,
                );
            }

            if ($eventBlocks !== []) {
                $sessions[] = new ResultsBookSession(
                    session: (int) $sessionNumber,
                    events: $eventBlocks,
                );
            }
        }

        $dateLabel = $competition->start_date->equalTo($competition->end_date)
            ? $competition->start_date->translatedFormat('d M Y')
            : $competition->start_date->translatedFormat('d M Y').' – '.$competition->end_date->translatedFormat('d M Y');

        return new ResultsBookDocument(
            competitionId: $competition->id,
            competitionName: $competition->name,
            venue: (string) $competition->venue,
            city: (string) $competition->city,
            dateLabel: $dateLabel,
            poolLanes: $laneCount,
            sessions: $sessions,
            printedAt: now(),
        );
    }

    /**
     * @return array<int, int> registration_id => rank
     */
    private function ranksFor(Event $event, AgeGroup $ageGroup): array
    {
        $results = Result::query()
            ->with('heatLane.registration')
            ->whereHas('heatLane.heat', function ($query) use ($event, $ageGroup): void {
                $query->where('event_id', $event->id)
                    ->where('age_group_id', $ageGroup->id);
            })
            ->get();

        $map = [];

        foreach ($this->ranking->rankResults($results) as $entry) {
            if ($entry->rank === null) {
                continue;
            }

            $result = $results->firstWhere('id', $entry->resultId);
            $registrationId = $result?->heatLane?->registration_id;
            if ($registrationId !== null) {
                $map[$registrationId] = $entry->rank;
            }
        }

        return $map;
    }
}
