<?php

namespace App\Services;

use App\DataTransferObjects\StartListAgeGroupBlock;
use App\DataTransferObjects\StartListDocument;
use App\DataTransferObjects\StartListEventBlock;
use App\DataTransferObjects\StartListHeat;
use App\DataTransferObjects\StartListLane;
use App\DataTransferObjects\StartListSession;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use Illuminate\Support\Collection;

class StartListBuilder
{
    public function build(
        Competition $competition,
        ?int $session = null,
        ?int $eventId = null,
        ?int $ageGroupId = null,
        ?int $clubId = null,
    ): StartListDocument {
        $eventsQuery = $competition->events()
            ->with([
                'heats' => fn ($query) => $query
                    ->where('round', 'final')
                    ->orderBy('heat_number')
                    ->with([
                        'ageGroup',
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
                $heats = $event->heats;
                if ($ageGroupId !== null) {
                    $heats = $heats->where('age_group_id', $ageGroupId);
                }

                $grouped = $heats->groupBy('age_group_id')
                    ->sortBy(fn (Collection $groupHeats): int => (int) ($groupHeats->first()?->ageGroup?->sort_order ?? 0));

                $ageGroupBlocks = [];
                foreach ($grouped as $groupHeats) {
                    /** @var Heat $sample */
                    $sample = $groupHeats->first();
                    $ageGroup = $sample->ageGroup;
                    if ($ageGroup === null) {
                        continue;
                    }

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

                            if ($clubId !== null && ($club?->id !== $clubId)) {
                                $lanes[] = new StartListLane(
                                    laneNumber: $laneNumber,
                                    registrationId: null,
                                    athleteName: null,
                                    birthYear: null,
                                    ageGroupCode: null,
                                    clubName: null,
                                    city: null,
                                    seedTimeMs: null,
                                );

                                continue;
                            }

                            $lanes[] = new StartListLane(
                                laneNumber: $laneNumber,
                                registrationId: $registration?->id,
                                athleteName: $athlete?->full_name,
                                birthYear: $athlete?->birth_year,
                                ageGroupCode: $ageGroup->display_code ?: $ageGroup->code,
                                clubName: $club?->name,
                                city: $club?->city,
                                seedTimeMs: $registration?->seed_time_ms,
                                clubId: $club?->id,
                            );
                        }

                        if ($clubId !== null && collect($lanes)->every(fn (StartListLane $lane): bool => $lane->isEmpty())) {
                            continue;
                        }

                        $heatBlocks[] = new StartListHeat(
                            heatId: $heat->id,
                            heatNumber: $heat->heat_number,
                            lanes: $lanes,
                        );
                    }

                    if ($heatBlocks === []) {
                        continue;
                    }

                    $ageGroupBlocks[] = new StartListAgeGroupBlock(
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

                $eventBlocks[] = new StartListEventBlock(
                    eventId: $event->id,
                    eventNumber: $event->event_number,
                    eventName: $event->formattedName(),
                    session: (int) $event->session,
                    sortOrder: (int) $event->sort_order,
                    ageGroups: $ageGroupBlocks,
                );
            }

            if ($eventBlocks !== []) {
                $sessions[] = new StartListSession(
                    session: (int) $sessionNumber,
                    events: $eventBlocks,
                );
            }
        }

        $dateLabel = $competition->start_date->equalTo($competition->end_date)
            ? $competition->start_date->translatedFormat('d M Y')
            : $competition->start_date->translatedFormat('d M Y').' – '.$competition->end_date->translatedFormat('d M Y');

        return new StartListDocument(
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
}
