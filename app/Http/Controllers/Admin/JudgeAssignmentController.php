<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AssignEventJudges;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Event;
use App\Models\User;
use App\Support\ListPaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JudgeAssignmentController extends Controller
{
    public function edit(Request $request, Competition $competition): View
    {
        $this->authorize('seed', $competition);

        $allEvents = $this->eventsForCompetition($competition);
        $filtered = $this->filterEvents($allEvents, $request);
        $assignedCount = $allEvents->filter(fn (Event $event): bool => $event->judges->isNotEmpty())->count();
        $unassignedCount = $allEvents->count() - $assignedCount;

        return view('admin.judges.edit', [
            'competition' => $competition,
            'events' => ListPaginator::for($filtered),
            'judges' => $this->eligibleJudges($competition),
            'sessions' => $allEvents->pluck('session')->unique()->sort()->values(),
            'eventTotal' => $allEvents->count(),
            'assignedCount' => $assignedCount,
            'unassignedCount' => $unassignedCount,
            'filteredCount' => $filtered->count(),
            'unassignedFilteredCount' => $filtered->filter(fn (Event $event): bool => $event->judges->isEmpty())->count(),
            'filters' => [
                'q' => trim((string) $request->input('q', '')),
                'session' => (string) $request->input('session', ''),
                'status' => (string) $request->input('status', ''),
            ],
        ]);
    }

    public function update(Request $request, Competition $competition, AssignEventJudges $assign): RedirectResponse
    {
        $this->authorize('seed', $competition);

        $data = $request->validate([
            'intent' => ['required', Rule::in(['event', 'fill_empty', 'fill_session', 'replace_filtered'])],
            'event_id' => ['nullable', 'integer'],
            'judge_ids' => ['nullable', 'array'],
            'judge_ids.*' => ['integer', 'exists:users,id'],
            'q' => ['nullable', 'string'],
            'session' => ['nullable'],
            'status' => ['nullable', 'string'],
        ]);

        $intent = $data['intent'];
        $judgeIds = collect($data['judge_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (in_array($intent, ['fill_empty', 'fill_session', 'replace_filtered'], true) && $judgeIds === []) {
            return back()->withErrors(['judge_ids' => 'Pilih minimal satu juri.']);
        }

        $allEvents = $this->eventsForCompetition($competition);
        $filtered = $this->filterEvents($allEvents, $request);

        return match ($intent) {
            'event' => $this->saveEvent($competition, $request->integer('event_id'), $judgeIds, $assign),
            'fill_empty' => $this->fillEmpty($filtered, $judgeIds, $assign),
            'fill_session' => $this->fillSession($competition, $allEvents, $request->integer('event_id'), $judgeIds, $assign),
            'replace_filtered' => $this->replaceFiltered($filtered, $judgeIds, $assign),
        };
    }

    /**
     * @return Collection<int, Event>
     */
    private function eventsForCompetition(Competition $competition): Collection
    {
        return $competition->events()
            ->with(['judges:id,name,role'])
            ->orderBy('session')
            ->orderBy('sort_order')
            ->orderBy('event_number')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function eligibleJudges(Competition $competition): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereIn('role', [UserRole::Juri, UserRole::Panitia, UserRole::SuperAdmin])
            ->withCount([
                'judgedEvents as assigned_events_count' => fn ($query) => $query->where('events.competition_id', $competition->id),
            ])
            ->orderBy('name')
            ->get()
            ->sortBy(fn (User $user): string => $this->judgeSortKey($user))
            ->values();
    }

    private function judgeSortKey(User $user): string
    {
        $priority = match ($user->role) {
            UserRole::Juri => '0',
            UserRole::Panitia => '1',
            UserRole::SuperAdmin => '2',
        };

        return $priority.'-'.$user->name;
    }

    /**
     * @param  Collection<int, Event>  $events
     * @return Collection<int, Event>
     */
    private function filterEvents(Collection $events, Request $request): Collection
    {
        $search = mb_strtolower(trim((string) $request->input('q', '')));
        $session = $request->input('session', '');
        $status = (string) $request->input('status', '');

        return $events
            ->when($search !== '', function (Collection $items) use ($search): Collection {
                return $items->filter(function (Event $event) use ($search): bool {
                    $haystack = mb_strtolower(implode(' ', [
                        (string) $event->event_number,
                        $event->paddedEventNumber(),
                        $event->formattedName(),
                        $event->programName(),
                        $event->stroke->label(),
                        $event->gender->label(),
                    ]));

                    return str_contains($haystack, $search);
                })->values();
            })
            ->when($session !== '' && $session !== null, fn (Collection $items): Collection => $items
                ->where('session', (int) $session)
                ->values())
            ->when($status !== '', function (Collection $items) use ($status): Collection {
                return $items->filter(function (Event $event) use ($status): bool {
                    return match ($status) {
                        'unassigned' => $event->judges->isEmpty(),
                        'assigned' => $event->judges->isNotEmpty(),
                        default => true,
                    };
                })->values();
            });
    }

    /**
     * @param  list<int>  $judgeIds
     */
    private function saveEvent(Competition $competition, int $eventId, array $judgeIds, AssignEventJudges $assign): RedirectResponse
    {
        $event = $competition->events()->whereKey($eventId)->first();
        abort_unless($event instanceof Event, 404);

        $assign->syncMany([$event], $judgeIds);

        return back()
            ->with('status', 'Penugasan nomor '.$event->paddedEventNumber().' disimpan.')
            ->with('saved_event_id', $event->id);
    }

    /**
     * @param  Collection<int, Event>  $filtered
     * @param  list<int>  $judgeIds
     */
    private function fillEmpty(Collection $filtered, array $judgeIds, AssignEventJudges $assign): RedirectResponse
    {
        $targets = $filtered->filter(fn (Event $event): bool => $event->judges->isEmpty())->values();
        $count = $assign->syncMany($targets, $judgeIds);

        $message = $count === 0
            ? 'Tidak ada nomor kosong yang cocok dengan saringan.'
            : $count.' nomor yang masih kosong diisi juri.';

        return back()->with('status', $message);
    }

    /**
     * @param  Collection<int, Event>  $allEvents
     * @param  list<int>  $judgeIds
     */
    private function fillSession(
        Competition $competition,
        Collection $allEvents,
        int $eventId,
        array $judgeIds,
        AssignEventJudges $assign,
    ): RedirectResponse {
        $event = $allEvents->firstWhere('id', $eventId);
        abort_unless($event instanceof Event && $event->competition_id === $competition->id, 404);

        $targets = $allEvents
            ->filter(fn (Event $candidate): bool => $candidate->session === $event->session
                && ($candidate->id === $event->id || $candidate->judges->isEmpty()))
            ->values();

        $count = $assign->syncMany($targets, $judgeIds);

        return back()
            ->with('status', $count.' nomor di sesi '.$event->session.' diisi juri.')
            ->with('saved_event_id', $event->id);
    }

    /**
     * @param  Collection<int, Event>  $filtered
     * @param  list<int>  $judgeIds
     */
    private function replaceFiltered(Collection $filtered, array $judgeIds, AssignEventJudges $assign): RedirectResponse
    {
        $count = $assign->syncMany($filtered, $judgeIds);

        return back()->with('status', 'Juri diganti di '.$count.' nomor.');
    }
}
