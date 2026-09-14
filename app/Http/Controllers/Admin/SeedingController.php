<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RunSeeding;
use App\Enums\RegistrationStatus;
use App\Exceptions\CannotLockSeedingException;
use App\Exceptions\CannotReseedLockedHeatsException;
use App\Exceptions\UnsupportedLaneCountException;
use App\Http\Controllers\Controller;
use App\Models\AgeGroup;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Support\ListPaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SeedingController extends Controller
{
    public function index(Request $request, Competition $competition): View
    {
        $this->authorize('seed', $competition);

        $events = $competition->events()->with(['heats.ageGroup', 'ageGroups'])->get();

        $eligibleCounts = $competition->registrations()
            ->eligibleForSeeding()
            ->selectRaw('event_id, age_group_id, COUNT(*) as aggregate')
            ->groupBy('event_id', 'age_group_id')
            ->get()
            ->mapWithKeys(fn ($row): array => [(int) $row->event_id.'-'.(int) $row->age_group_id => (int) $row->aggregate]);

        $pairs = collect();
        foreach ($events as $event) {
            foreach ($event->ageGroups as $group) {
                $heats = $event->heats->where('age_group_id', $group->id)->sortBy('heat_number');
                $entrantCount = $eligibleCounts[$event->id.'-'.$group->id] ?? 0;
                $empty = $heats->isEmpty() && $entrantCount === 0;
                $pairs->push([
                    'event' => $event,
                    'ageGroup' => $group,
                    'heatCount' => $heats->count(),
                    'entrantCount' => $entrantCount,
                    'empty' => $empty,
                    'locked' => $heats->isNotEmpty() && $heats->every(fn (Heat $heat): bool => $heat->isLocked()),
                    'seeded' => $heats->isNotEmpty(),
                ]);
            }
        }

        $unseeded = $pairs->where('seeded', false)->where('empty', false)->count();
        $unlocked = $pairs->where('seeded', true)->where('locked', false)->count();
        $lockedCount = $pairs->where('locked', true)->count();
        $seededCount = $pairs->where('seeded', true)->count();
        $emptyCount = $pairs->where('empty', true)->count();
        $pairTotal = $pairs->count();
        $pendingCount = $competition->registrations()
            ->where('status', RegistrationStatus::Pending)
            ->count();
        $verifiedCount = $competition->registrations()
            ->eligibleForSeeding()
            ->count();

        return view('admin.seeding.index', [
            'competition' => $competition,
            'pairs' => ListPaginator::for($this->filterPairs($pairs, $request)),
            'pairTotal' => $pairTotal,
            'seededCount' => $seededCount,
            'lockedCount' => $lockedCount,
            'emptyCount' => $emptyCount,
            'unseeded' => $unseeded,
            'unlocked' => $unlocked,
            'pendingCount' => $pendingCount,
            'verifiedCount' => $verifiedCount,
            'filterEvents' => $events,
            'filterAgeGroups' => $competition->ageGroups,
            'filters' => [
                'q' => trim((string) $request->query('q', '')),
                'event_id' => $request->query('event_id', ''),
                'age_group_id' => $request->query('age_group_id', ''),
                'status' => $request->query('status', ''),
            ],
        ]);
    }

    public function show(Competition $competition, Event $event, AgeGroup $ageGroup): View
    {
        $this->authorize('seed', $competition);
        abort_unless($event->competition_id === $competition->id, 404);
        abort_unless($ageGroup->competition_id === $competition->id, 404);

        $anyLocked = Heat::query()
            ->where('event_id', $event->id)
            ->where('age_group_id', $ageGroup->id)
            ->where('round', 'final')
            ->whereNotNull('locked_at')
            ->exists();

        $heats = Heat::query()
            ->where('event_id', $event->id)
            ->where('age_group_id', $ageGroup->id)
            ->where('round', 'final')
            ->with(['lanes.registration.athlete.club'])
            ->orderBy('heat_number')
            ->paginate(ListPaginator::PER_PAGE)
            ->withQueryString();

        return view('admin.seeding.show', [
            'competition' => $competition,
            'event' => $event,
            'ageGroup' => $ageGroup,
            'heats' => $heats,
            'laneCount' => $competition->pool_lanes,
            'anyLocked' => $anyLocked,
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

        return back()->with(
            'status',
            $heats->count() > 0
                ? $heats->count().' seri dihasilkan.'
                : 'Tidak ada seri baru. Nomor yang sudah dikunci dilewati.',
        );
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

    /**
     * @param  Collection<int, array{event: Event, ageGroup: AgeGroup, heatCount: int, locked: bool, seeded: bool}>  $pairs
     * @return Collection<int, array{event: Event, ageGroup: AgeGroup, heatCount: int, locked: bool, seeded: bool}>
     */
    private function filterPairs(Collection $pairs, Request $request): Collection
    {
        $search = mb_strtolower(trim((string) $request->query('q', '')));
        $eventId = $request->integer('event_id');
        $ageGroupId = $request->integer('age_group_id');
        $status = (string) $request->query('status', '');

        return $pairs
            ->when($search !== '', function (Collection $items) use ($search): Collection {
                return $items->filter(function (array $pair) use ($search): bool {
                    $haystack = mb_strtolower(trim(
                        $pair['event']->event_number.' '.$pair['event']->formattedName().' '.$pair['ageGroup']->name
                    ));

                    return str_contains($haystack, $search);
                })->values();
            })
            ->when($eventId > 0, fn (Collection $items): Collection => $items
                ->filter(fn (array $pair): bool => $pair['event']->id === $eventId)
                ->values())
            ->when($ageGroupId > 0, fn (Collection $items): Collection => $items
                ->filter(fn (array $pair): bool => $pair['ageGroup']->id === $ageGroupId)
                ->values())
            ->when($status !== '', function (Collection $items) use ($status): Collection {
                return $items->filter(function (array $pair) use ($status): bool {
                    return match ($status) {
                        'unseeded' => ! $pair['seeded'] && ! $pair['empty'],
                        'empty' => $pair['empty'],
                        'preview' => $pair['seeded'] && ! $pair['locked'],
                        'locked' => $pair['locked'],
                        default => true,
                    };
                })->values();
            });
    }

    private function lockCompetition(Competition $competition): void
    {
        if ($competition->eventsPendingSeeding()->isNotEmpty()) {
            throw new CannotLockSeedingException(
                'Penguncian ditolak karena masih ada nomor dengan peserta yang belum diseeding.',
            );
        }

        Heat::query()
            ->whereHas('event', fn ($query) => $query->where('competition_id', $competition->id))
            ->whereNull('locked_at')
            ->update(['locked_at' => now()]);
    }

    private function lockEvent(Competition $competition, Event $event): void
    {
        $pending = $competition->eventsPendingSeeding()
            ->contains(fn (Event $item): bool => $item->id === $event->id);

        if ($pending) {
            throw new CannotLockSeedingException(
                'Penguncian ditolak karena nomor lomba ini masih punya peserta yang belum diseeding.',
            );
        }

        Heat::query()
            ->where('event_id', $event->id)
            ->whereNull('locked_at')
            ->update(['locked_at' => now()]);
    }
}
