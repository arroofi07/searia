<?php

namespace App\Http\Controllers\Judge;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Heat;
use App\Models\User;
use Illuminate\Http\Request;
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
                ->with(['competition', 'ageGroups', 'heats.lanes'])
                ->whereHas('heats')
                ->orderBy('competition_id')
                ->orderBy('session')
                ->orderBy('sort_order')
                ->orderBy('event_number')
                ->get()
            : $user->judgedEvents()
                ->with(['competition', 'ageGroups', 'heats.lanes'])
                ->orderBy('session')
                ->orderBy('sort_order')
                ->orderBy('event_number')
                ->get();

        $tasks = $events->map(function (Event $event) {
            $heats = $event->heats->sortBy([
                fn (Heat $heat) => $heat->isResultsLocked() ? 1 : 0,
                fn (Heat $heat) => $heat->age_group_id,
                fn (Heat $heat) => $heat->heat_number,
            ])->values();

            $locked = $heats->filter->isResultsLocked()->count();
            $total = $heats->count();

            return [
                'event' => $event,
                'heats' => $heats,
                'locked' => $locked,
                'total' => $total,
                'complete' => $total > 0 && $locked === $total,
            ];
        });

        return view('judge.tasks.index', [
            'tasks' => $tasks,
            'empty' => $tasks->isEmpty(),
        ]);
    }
}
