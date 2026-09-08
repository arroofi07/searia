<?php

namespace App\Http\Controllers;

use App\Enums\CompetitionStatus;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\Result;
use App\Services\RankingCalculator;
use App\Support\SwimTime;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AthleteResultController extends Controller
{
    public function show(Request $request, Competition $competition, Athlete $athlete, RankingCalculator $ranking): View
    {
        $this->authorizeAthleteResults($request, $competition, $athlete);

        $results = Result::query()
            ->with(['heatLane.heat.event', 'heatLane.heat.ageGroup', 'heatLane.registration'])
            ->whereHas('heatLane.registration', function ($query) use ($competition, $athlete): void {
                $query->where('competition_id', $competition->id)
                    ->where('athlete_id', $athlete->id);
            })
            ->get();

        $rows = $results->map(function (Result $result) use ($ranking) {
            $heat = $result->heatLane?->heat;
            $event = $heat?->event;
            $ageGroup = $heat?->ageGroup;
            $rank = null;
            if ($event && $ageGroup) {
                $table = $ranking->forEventAgeGroup($event, $ageGroup);
                $match = collect($table->entries)->firstWhere('resultId', $result->id);
                $rank = $match?->rank;
            }

            $seed = $result->heatLane?->registration?->seed_time_ms;
            $time = $result->time_ms;
            $delta = ($time !== null && $seed !== null) ? $time - $seed : null;
            $isPb = $result->status === \App\Enums\ResultStatus::Ok
                && $time !== null
                && ($seed === null || $time < $seed);

            return [
                'event' => $event,
                'age_group' => $ageGroup,
                'seed_ms' => $seed,
                'time_ms' => $time,
                'status' => $result->status,
                'rank' => $rank,
                'delta_ms' => $delta,
                'is_pb' => $isPb,
                'heat_number' => $heat?->heat_number,
                'lane_number' => $result->heatLane?->lane_number,
            ];
        })->sortBy(fn (array $row) => $row['event']?->event_number ?? 0)->values();

        $history = Result::query()
            ->with(['heatLane.heat.event.competition', 'heatLane.registration'])
            ->whereHas('heatLane.registration', fn ($q) => $q->where('athlete_id', $athlete->id))
            ->whereHas('heatLane.heat.event', fn ($q) => $q->where('competition_id', '!=', $competition->id))
            ->latest('recorded_at')
            ->limit(20)
            ->get();

        return view('results.athlete', [
            'competition' => $competition,
            'athlete' => $athlete,
            'rows' => $rows,
            'history' => $history,
            'preview' => $competition->status !== CompetitionStatus::Published,
            'formatTime' => fn (?int $ms): string => SwimTime::formatMilliseconds($ms),
        ]);
    }

    private function authorizeAthleteResults(Request $request, Competition $competition, Athlete $athlete): void
    {
        $hasEntry = $athlete->registrations()->where('competition_id', $competition->id)->exists();
        abort_unless($hasEntry, 404);

        if ($competition->status === CompetitionStatus::Published) {
            return;
        }

        if ($request->user()?->managesMasterData()) {
            return;
        }

        throw new NotFoundHttpException;
    }
}
