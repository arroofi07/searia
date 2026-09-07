<?php

namespace App\Http\Controllers\Public;

use App\Enums\CompetitionStatus;
use App\Enums\ResultStatus;
use App\Http\Controllers\Controller;
use App\Models\Athlete;
use App\Models\Result;
use App\Support\SwimTime;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AthleteSearchController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $athletes = collect();

        if (mb_strlen($q) >= 2) {
            $athletes = Athlete::query()
                ->with('club')
                ->where('is_active', true)
                ->where('full_name', 'like', '%'.$q.'%')
                ->whereHas('registrations.competition', fn ($query) => $query->where('status', CompetitionStatus::Published))
                ->orderBy('full_name')
                ->limit(30)
                ->get(['id', 'club_id', 'full_name', 'gender', 'birth_year']);
        }

        return view('public.athletes.search', [
            'q' => $q,
            'athletes' => $athletes,
        ]);
    }

    public function show(Athlete $athlete): View
    {
        abort_unless($athlete->is_active, 404);

        $results = Result::query()
            ->with([
                'heatLane.heat.event.competition',
                'heatLane.registration',
            ])
            ->whereHas('heatLane.registration', fn ($q) => $q->where('athlete_id', $athlete->id))
            ->whereHas('heatLane.heat.event.competition', fn ($q) => $q->where('status', CompetitionStatus::Published))
            ->orderByDesc('recorded_at')
            ->get();

        $bestByEvent = [];
        foreach ($results as $result) {
            if ($result->status !== ResultStatus::Ok || $result->time_ms === null) {
                continue;
            }
            $event = $result->heatLane?->heat?->event;
            if ($event === null) {
                continue;
            }
            $key = $event->distance.'-'.$event->stroke->value.'-'.$event->gender->value;
            if (! isset($bestByEvent[$key]) || $result->time_ms < $bestByEvent[$key]['time_ms']) {
                $bestByEvent[$key] = [
                    'label' => $event->formattedName(),
                    'time_ms' => $result->time_ms,
                    'competition' => $event->competition?->name,
                ];
            }
        }

        return view('public.athletes.show', [
            'athlete' => $athlete->load('club'),
            'results' => $results,
            'bestByEvent' => array_values($bestByEvent),
            'formatTime' => fn (?int $ms): string => SwimTime::formatMilliseconds($ms),
        ]);
    }
}
