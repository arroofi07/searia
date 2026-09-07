<?php

namespace App\Http\Controllers\Judge;

use App\Actions\LockHeat;
use App\Enums\DisqualificationCode;
use App\Enums\ResultStatus;
use App\Exceptions\CannotLockHeatException;
use App\Http\Controllers\Controller;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Result;
use App\Policies\ResultPolicy;
use App\Support\SwimTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HeatResultController extends Controller
{
    public function show(Request $request, Heat $heat): View
    {
        $this->authorizeHeat($request, $heat);

        $heat->load([
            'event.competition',
            'ageGroup',
            'lanes' => fn ($q) => $q->orderBy('lane_number')->with([
                'result',
                'registration.athlete.club',
            ]),
        ]);

        $competition = $heat->event->competition;
        $laneCount = $competition->pool_lanes;
        $lanesByNumber = $heat->lanes->keyBy('lane_number');

        $rows = [];
        for ($n = 1; $n <= $laneCount; $n++) {
            /** @var HeatLane|null $lane */
            $lane = $lanesByNumber->get($n);
            $rows[] = [
                'lane_number' => $n,
                'lane' => $lane,
                'empty' => $lane === null || $lane->registration_id === null,
            ];
        }

        $siblings = Heat::query()
            ->where('event_id', $heat->event_id)
            ->where('age_group_id', $heat->age_group_id)
            ->orderBy('heat_number')
            ->get();

        $index = $siblings->search(fn (Heat $item): bool => $item->id === $heat->id);
        $previous = $index > 0 ? $siblings[$index - 1] : null;
        $next = ($index !== false && $index < $siblings->count() - 1) ? $siblings[$index + 1] : null;

        return view('judge.heats.show', [
            'heat' => $heat,
            'rows' => $rows,
            'statuses' => ResultStatus::casesOrdered(),
            'dsqCodes' => DisqualificationCode::cases(),
            'previous' => $previous,
            'next' => $next,
            'heatIndex' => $index === false ? 0 : $index + 1,
            'heatTotal' => $siblings->count(),
            'fastInput' => $competition->allowsFastTimeInput(),
            'canLock' => $heat->isFullyRecorded() && ! $heat->isResultsLocked(),
            'locked' => $heat->isResultsLocked(),
            'formatTime' => fn (?int $ms): string => SwimTime::formatMilliseconds($ms),
        ]);
    }

    public function lock(Request $request, Heat $heat, LockHeat $lockHeat): RedirectResponse
    {
        $this->authorize('lock', [Result::class, $heat]);

        try {
            $lockHeat->handle($heat, $request->user(), $request->ip());
        } catch (CannotLockHeatException $e) {
            return back()->withErrors(['lock' => $e->getMessage()]);
        }

        return back()->with('status', 'Seri dikunci.');
    }

    private function authorizeHeat(Request $request, Heat $heat): void
    {
        $heat->loadMissing('event');
        abort_unless(app(ResultPolicy::class)->viewEvent($request->user(), $heat->event), 403);
    }
}
