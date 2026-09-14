<?php

namespace App\Http\Controllers\Judge;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\User;
use App\Support\ListPaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TaskListController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->isJuri() || $user->managesMasterData(), 403);

        $events = $user->managesMasterData()
            ? Event::query()
                ->with(['competition', 'heats.ageGroup', 'heats.lanes.result'])
                ->whereHas('heats')
                ->orderBy('competition_id')
                ->orderBy('session')
                ->orderBy('sort_order')
                ->orderBy('event_number')
                ->get()
            : $user->judgedEvents()
                ->with(['competition', 'heats.ageGroup', 'heats.lanes.result'])
                ->orderBy('session')
                ->orderBy('sort_order')
                ->orderBy('event_number')
                ->get();

        $tasks = $events->map(function (Event $event) {
            $heats = $event->heats->sortBy([
                fn (Heat $heat) => $heat->isResultsLocked() ? 1 : 0,
                fn (Heat $heat) => $heat->age_group_id,
                fn (Heat $heat) => $heat->heat_number,
            ])->values()->map(fn (Heat $heat): array => $this->heatSummary($heat, $event));

            $locked = $heats->where('locked', true)->count();
            $total = $heats->count();

            return [
                'event' => $event,
                'heats' => $heats,
                'locked' => $locked,
                'total' => $total,
                'complete' => $total > 0 && $locked === $total,
            ];
        });

        $allHeats = $tasks->pluck('heats')->flatten(1);
        $pendingCount = $allHeats->where('locked', false)->count();
        $lockedCount = $allHeats->where('locked', true)->count();
        $readyCount = $allHeats->where('ready', true)->count();
        $statusFilter = (string) $request->query('status', '');

        $filtered = $this->filterTasks($tasks, $statusFilter);
        $nextHeat = $allHeats->first(fn (array $row): bool => ! $row['locked']);

        return view('judge.tasks.index', [
            'tasks' => ListPaginator::for($filtered),
            'empty' => $tasks->isEmpty(),
            'manages' => $user->managesMasterData(),
            'firstCompetition' => $tasks->first()['event']->competition ?? null,
            'pendingCount' => $pendingCount,
            'lockedCount' => $lockedCount,
            'readyCount' => $readyCount,
            'heatTotal' => $allHeats->count(),
            'nextHeat' => $nextHeat,
            'filters' => ['status' => $statusFilter],
        ]);
    }

    /**
     * @return array{heat: Heat, occupied: int, recorded: int, locked: bool, ready: bool}
     */
    private function heatSummary(Heat $heat, Event $event): array
    {
        $heat->setRelation('event', $event);
        $occupied = $heat->lanes->whereNotNull('registration_id');
        $recorded = $occupied->filter(fn (HeatLane $lane): bool => $lane->result !== null)->count();
        $locked = $heat->isResultsLocked();

        return [
            'heat' => $heat,
            'occupied' => $occupied->count(),
            'recorded' => $recorded,
            'locked' => $locked,
            'ready' => ! $locked && $occupied->isNotEmpty() && $recorded >= $occupied->count(),
        ];
    }

    /**
     * @param  Collection<int, array{event: Event, heats: Collection<int, array{heat: Heat, occupied: int, recorded: int, locked: bool, ready: bool}>, locked: int, total: int, complete: bool}>  $tasks
     * @return Collection<int, array{event: Event, heats: Collection<int, array{heat: Heat, occupied: int, recorded: int, locked: bool, ready: bool}>, locked: int, total: int, complete: bool}>
     */
    private function filterTasks(Collection $tasks, string $status): Collection
    {
        if ($status === '') {
            return $tasks->values();
        }

        return $tasks
            ->map(function (array $task) use ($status): array {
                $heats = $task['heats']->filter(function (array $row) use ($status): bool {
                    return match ($status) {
                        'pending' => ! $row['locked'],
                        'ready' => $row['ready'],
                        'locked' => $row['locked'],
                        default => true,
                    };
                })->values();

                return [
                    ...$task,
                    'heats' => $heats,
                    'locked' => $heats->where('locked', true)->count(),
                    'total' => $heats->count(),
                    'complete' => $heats->isNotEmpty() && $heats->every(fn (array $row): bool => $row['locked']),
                ];
            })
            ->filter(fn (array $task): bool => $task['heats']->isNotEmpty())
            ->values();
    }
}
