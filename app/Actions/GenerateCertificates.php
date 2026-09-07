<?php

namespace App\Actions;

use App\Enums\CompetitionStatus;
use App\Enums\ResultStatus;
use App\Models\Certificate;
use App\Models\Competition;
use App\Models\Result;
use App\Services\RankingCalculator;
use Illuminate\Support\Facades\DB;

class GenerateCertificates
{
    public function __construct(private readonly RankingCalculator $ranking) {}

    public function handle(Competition $competition): int
    {
        if ($competition->status !== CompetitionStatus::Published) {
            return 0;
        }

        return DB::transaction(function () use ($competition): int {
            $created = 0;
            $competition->loadMissing('ageGroups', 'events');

            $results = Result::query()
                ->with(['heatLane.heat.event', 'heatLane.heat.ageGroup', 'heatLane.registration.athlete'])
                ->whereHas('heatLane.heat.event', fn ($q) => $q->where('competition_id', $competition->id))
                ->whereHas('heatLane', fn ($q) => $q->whereNotNull('registration_id'))
                ->get();

            foreach ($results as $result) {
                $lane = $result->heatLane;
                $heat = $lane?->heat;
                $event = $heat?->event;
                $athlete = $lane?->registration?->athlete;

                if ($event === null || $athlete === null) {
                    continue;
                }

                $certificate = Certificate::query()->updateOrCreate(
                    [
                        'competition_id' => $competition->id,
                        'athlete_id' => $athlete->id,
                        'event_id' => $event->id,
                        'type' => 'participant',
                    ],
                    [
                        'age_group_id' => $heat->age_group_id,
                        'result_id' => $result->id,
                        'rank' => null,
                        'time_ms' => $result->time_ms,
                        'status' => $result->status,
                    ],
                );

                if ($certificate->wasRecentlyCreated) {
                    $created++;
                }
            }

            foreach ($competition->events as $event) {
                $ageGroupIds = $event->heats()->distinct()->pluck('age_group_id');
                foreach ($ageGroupIds as $ageGroupId) {
                    $ageGroup = $event->competition->ageGroups->firstWhere('id', $ageGroupId)
                        ?? \App\Models\AgeGroup::query()->find($ageGroupId);

                    if ($ageGroup === null) {
                        continue;
                    }

                    $table = $this->ranking->forEventAgeGroup($event, $ageGroup);
                    foreach ($table->entries as $entry) {
                        if ($entry->rank === null || $entry->rank > 3 || $entry->status !== ResultStatus::Ok) {
                            continue;
                        }

                        $certificate = Certificate::query()->updateOrCreate(
                            [
                                'competition_id' => $competition->id,
                                'athlete_id' => $entry->athleteId,
                                'event_id' => $event->id,
                                'type' => 'winner',
                            ],
                            [
                                'age_group_id' => $ageGroup->id,
                                'result_id' => $entry->resultId,
                                'rank' => $entry->rank,
                                'time_ms' => $entry->timeMs,
                                'status' => $entry->status,
                            ],
                        );

                        if ($certificate->wasRecentlyCreated) {
                            $created++;
                        }
                    }
                }
            }

            return $created;
        });
    }
}
