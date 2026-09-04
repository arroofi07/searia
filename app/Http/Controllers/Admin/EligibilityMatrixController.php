<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEligibilityMatrixRequest;
use App\Models\Competition;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EligibilityMatrixController extends Controller
{
    public function show(Competition $competition): View
    {
        $this->authorize('update', $competition);

        $competition->load(['ageGroups', 'events']);

        $eligible = DB::table('event_age_group')
            ->whereIn('event_id', $competition->events->modelKeys())
            ->get()
            ->map(fn ($row): string => $row->event_id.':'.$row->age_group_id)
            ->all();

        $registrationCounts = Registration::query()
            ->where('competition_id', $competition->id)
            ->selectRaw('event_id, age_group_id, count(*) as total')
            ->groupBy('event_id', 'age_group_id')
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->event_id.':'.$row->age_group_id => (int) $row->total]);

        return view('admin.competitions.eligibility', [
            'competition' => $competition,
            'eligible' => $eligible,
            'registrationCounts' => $registrationCounts,
        ]);
    }

    public function update(UpdateEligibilityMatrixRequest $request, Competition $competition): JsonResponse
    {
        $competition->load(['events', 'ageGroups']);

        $desired = collect($request->validated('pairs') ?? [])
            ->map(fn (array $pair): string => $pair['event_id'].':'.$pair['age_group_id'])
            ->unique()
            ->values();

        $current = DB::table('event_age_group')
            ->whereIn('event_id', $competition->events->modelKeys())
            ->get()
            ->map(fn ($row): string => $row->event_id.':'.$row->age_group_id);

        $removed = $current->diff($desired)->values();
        $affected = $this->affectedRegistrationCount($competition, $removed);

        if ($affected > 0 && ! $request->boolean('confirm_affected')) {
            return response()->json([
                'ok' => false,
                'requires_confirmation' => true,
                'affected_registrations' => $affected,
                'message' => "Pencabutan kelayakan memengaruhi {$affected} pendaftaran. Konfirmasi untuk menyimpan.",
            ], 409);
        }

        DB::transaction(function () use ($competition, $desired): void {
            foreach ($competition->events as $event) {
                $ids = $desired
                    ->filter(fn (string $key): bool => str_starts_with($key, $event->id.':'))
                    ->map(fn (string $key): int => (int) explode(':', $key)[1])
                    ->values()
                    ->all();

                $event->ageGroups()->sync($ids);
            }
        });

        return response()->json([
            'ok' => true,
            'count' => $desired->count(),
        ]);
    }

    /**
     * @param  Collection<int, string>  $removedKeys
     */
    private function affectedRegistrationCount(Competition $competition, Collection $removedKeys): int
    {
        if ($removedKeys->isEmpty()) {
            return 0;
        }

        return Registration::query()
            ->where('competition_id', $competition->id)
            ->where(function ($query) use ($removedKeys): void {
                foreach ($removedKeys as $key) {
                    [$eventId, $ageGroupId] = array_map('intval', explode(':', $key));
                    $query->orWhere(function ($inner) use ($eventId, $ageGroupId): void {
                        $inner->where('event_id', $eventId)->where('age_group_id', $ageGroupId);
                    });
                }
            })
            ->count();
    }
}
