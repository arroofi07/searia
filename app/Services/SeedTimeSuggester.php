<?php

namespace App\Services;

use App\Enums\CompetitionType;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Result;

class SeedTimeSuggester
{
    public function suggest(Athlete $athlete, Event $event, Competition $competition): ?int
    {
        $best = Result::query()
            ->where('status', 'ok')
            ->whereNotNull('time_ms')
            ->whereHas('heatLane.registration', function ($query) use ($athlete): void {
                $query->where('athlete_id', $athlete->id);
            })
            ->whereHas('heatLane.heat.event', function ($query) use ($event, $competition): void {
                $query
                    ->where('distance', $event->distance)
                    ->where('stroke', $event->stroke)
                    ->where('equipment', $event->equipment)
                    ->where('gender', $event->gender)
                    ->where('competition_id', '!=', $competition->id)
                    ->whereHas('competition', function ($competitionQuery) use ($competition): void {
                        $competitionQuery
                            ->where('type', CompetitionType::Official)
                            ->where('pool_length', $competition->pool_length);
                    });
            })
            ->orderBy('time_ms')
            ->first();

        return $best?->time_ms;
    }
}
