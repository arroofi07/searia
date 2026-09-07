<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JudgeAssignmentController extends Controller
{
    public function edit(Competition $competition): View
    {
        $this->authorize('seed', $competition);

        $judges = User::query()
            ->whereIn('role', [UserRole::Juri, UserRole::Panitia, UserRole::SuperAdmin])
            ->orderBy('name')
            ->get();

        $events = $competition->events()
            ->with(['judges:id,name'])
            ->orderBy('session')
            ->orderBy('sort_order')
            ->orderBy('event_number')
            ->get();

        return view('admin.judges.edit', [
            'competition' => $competition,
            'events' => $events,
            'judges' => $judges,
        ]);
    }

    public function update(Request $request, Competition $competition): RedirectResponse
    {
        $this->authorize('seed', $competition);

        $data = $request->validate([
            'assignments' => ['nullable', 'array'],
            'assignments.*' => ['array'],
            'assignments.*.*' => ['integer', 'exists:users,id'],
        ]);

        $assignments = $data['assignments'] ?? [];

        foreach ($competition->events as $event) {
            /** @var Event $event */
            $userIds = collect($assignments[(string) $event->id] ?? [])
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            $event->judges()->sync($userIds);
        }

        return back()->with('status', 'Penugasan juri disimpan.');
    }
}
