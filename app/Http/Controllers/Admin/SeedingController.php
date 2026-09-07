<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RunSeeding;
use App\Exceptions\CannotLockSeedingException;
use App\Exceptions\CannotReseedLockedHeatsException;
use App\Exceptions\UnsupportedLaneCountException;
use App\Http\Controllers\Controller;
use App\Models\AgeGroup;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeedingController extends Controller
{
    public function index(Competition $competition): View
    {
        $this->authorize('seed', $competition);

        $events = $competition->events()->with(['heats.ageGroup', 'ageGroups'])->get();
        $ageGroups = $competition->ageGroups;

        $pairs = [];
        foreach ($events as $event) {
            foreach ($event->ageGroups as $group) {
                $heats = $event->heats->where('age_group_id', $group->id)->sortBy('heat_number');
                $pairs[] = [
                    'event' => $event,
                    'ageGroup' => $group,
                    'heatCount' => $heats->count(),
                    'locked' => $heats->isNotEmpty() && $heats->every(fn (Heat $heat): bool => $heat->isLocked()),
                    'seeded' => $heats->isNotEmpty(),
                ];
            }
        }

        $unseeded = collect($pairs)->where('seeded', false)->count();
        $unlocked = collect($pairs)->where('seeded', true)->where('locked', false)->count();

        return view('admin.seeding.index', [
            'competition' => $competition,
            'pairs' => $pairs,
            'unseeded' => $unseeded,
            'unlocked' => $unlocked,
        ]);
    }

    public function show(Competition $competition, Event $event, AgeGroup $ageGroup): View
    {
        $this->authorize('seed', $competition);
        abort_unless($event->competition_id === $competition->id, 404);
        abort_unless($ageGroup->competition_id === $competition->id, 404);

        $heats = Heat::query()
            ->where('event_id', $event->id)
            ->where('age_group_id', $ageGroup->id)
            ->where('round', 'final')
            ->with(['lanes.registration.athlete.club'])
            ->orderBy('heat_number')
            ->get();

        return view('admin.seeding.show', [
            'competition' => $competition,
            'event' => $event,
            'ageGroup' => $ageGroup,
            'heats' => $heats,
            'laneCount' => $competition->pool_lanes,
        ]);
    }

    public function run(Request $request, Competition $competition, RunSeeding $seeding): RedirectResponse
    {
        $this->authorize('seed', $competition);

        $event = $request->filled('event_id')
            ? Event::query()->where('competition_id', $competition->id)->findOrFail($request->integer('event_id'))
            : null;
        $ageGroup = $request->filled('age_group_id')
            ? AgeGroup::query()->where('competition_id', $competition->id)->findOrFail($request->integer('age_group_id'))
            : null;
        $force = $request->boolean('force');

        try {
            $heats = $seeding->handle(
                $competition,
                $event,
                $ageGroup,
                $force,
                $request->user(),
                $request->ip(),
            );
        } catch (CannotReseedLockedHeatsException|UnsupportedLaneCountException $exception) {
            return back()->withErrors(['seeding' => $exception->getMessage()]);
        }

        return back()->with('status', $heats->count().' seri dihasilkan.');
    }

    public function lock(Request $request, Competition $competition): RedirectResponse
    {
        $this->authorize('seed', $competition);

        $eventId = $request->integer('event_id') ?: null;

        try {
            if ($eventId) {
                $event = Event::query()->where('competition_id', $competition->id)->findOrFail($eventId);
                $this->lockEvent($competition, $event);
            } else {
                $this->lockCompetition($competition);
            }
        } catch (CannotLockSeedingException $exception) {
            return back()->withErrors(['seeding' => $exception->getMessage()]);
        }

        \App\Support\PublicPageCache::bump();

        return back()->with('status', 'Seri dikunci.');
    }

    private function lockCompetition(Competition $competition): void
    {
        $unseeded = $competition->events()
            ->whereDoesntHave('heats')
            ->exists();

        if ($unseeded) {
            throw new CannotLockSeedingException(
                'Penguncian ditolak karena masih ada nomor lomba yang belum diseeding.',
            );
        }

        Heat::query()
            ->whereHas('event', fn ($query) => $query->where('competition_id', $competition->id))
            ->whereNull('locked_at')
            ->update(['locked_at' => now()]);
    }

    private function lockEvent(Competition $competition, Event $event): void
    {
        if (! $event->heats()->exists()) {
            throw new CannotLockSeedingException(
                'Penguncian ditolak karena nomor lomba ini belum diseeding.',
            );
        }

        Heat::query()
            ->where('event_id', $event->id)
            ->whereNull('locked_at')
            ->update(['locked_at' => now()]);
    }
}
