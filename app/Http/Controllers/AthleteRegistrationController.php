<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Exceptions\InvalidSwimTimeException;
use App\Http\Requests\StoreAthleteRegistrationRequest;
use App\Http\Requests\UpdateAthleteRegistrationRequest;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Registration;
use App\Services\AgeGroupResolver;
use App\Services\RegistrationDraft;
use App\Services\RegistrationValidator;
use App\Support\SwimTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AthleteRegistrationController extends Controller
{
    public function store(
        StoreAthleteRegistrationRequest $request,
        Athlete $athlete,
        RegistrationValidator $validator,
        AgeGroupResolver $ageGroups,
    ): RedirectResponse {
        $this->authorize('update', $athlete);

        $competition = Competition::query()->findOrFail($request->integer('competition_id'));
        $this->authorize('create', [Registration::class, $competition]);

        $eventIds = collect($request->validated('event_ids'))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $events = Event::query()
            ->where('competition_id', $competition->id)
            ->whereIn('id', $eventIds)
            ->get()
            ->keyBy('id');

        if ($events->count() !== $eventIds->count()) {
            return back()
                ->withInput()
                ->withErrors(['event_ids' => 'Nomor lomba bukan milik kejuaraan ini.']);
        }

        $seedTimes = $request->validated('seed_times') ?? [];
        $drafts = [];

        foreach ($eventIds as $eventId) {
            $event = $events->get($eventId);
            $seedInput = $seedTimes[$eventId] ?? $seedTimes[(string) $eventId] ?? null;

            $drafts[] = new RegistrationDraft(
                competition: $competition,
                athlete: $athlete,
                event: $event,
                seedTimeInput: is_string($seedInput) ? $seedInput : null,
            );
        }

        $messages = collect($validator->validateMany($drafts))
            ->flatten(1)
            ->pluck('message')
            ->unique()
            ->values();

        if ($messages->isNotEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['registration' => $messages->implode(' ')]);
        }

        $verifyNow = $request->boolean('verify_now');
        $ageGroup = $ageGroups->resolve($competition, $athlete->birth_year);

        try {
            DB::transaction(function () use ($drafts, $competition, $athlete, $ageGroup, $request, $verifyNow): void {
                foreach ($drafts as $draft) {
                    $this->persistEntry(
                        $competition,
                        $athlete,
                        $draft->event,
                        $ageGroup?->id,
                        SwimTime::parse($draft->seedTimeInput)?->milliseconds,
                        $request->user()->id,
                        $verifyNow,
                    );
                }
            });
        } catch (InvalidSwimTimeException $exception) {
            return back()->withInput()->withErrors(['registration' => $exception->getMessage()]);
        }

        $count = $eventIds->count();

        return redirect()
            ->route('athletes.show', [
                'athlete' => $athlete,
                'competition_id' => $competition->id,
            ])
            ->with('status', $count === 1
                ? 'Nomor lomba ditambahkan.'
                : $count.' nomor lomba ditambahkan.');
    }

    public function update(
        UpdateAthleteRegistrationRequest $request,
        Athlete $athlete,
        Registration $registration,
        RegistrationValidator $validator,
        AgeGroupResolver $ageGroups,
    ): RedirectResponse {
        $this->authorize('update', $athlete);
        $this->assertOwnedByAthlete($athlete, $registration);

        $event = Event::query()->findOrFail($request->integer('event_id'));

        try {
            $ms = SwimTime::parse($request->validated('seed_time'))?->milliseconds;
        } catch (InvalidSwimTimeException $exception) {
            return back()->withInput()->withErrors(['seed_time' => $exception->getMessage()]);
        }

        if ($event->is($registration->event)) {
            $wasRejected = $registration->status === RegistrationStatus::Rejected;

            $registration->update([
                'seed_time_ms' => $ms,
                'status' => $wasRejected ? RegistrationStatus::Pending : $registration->status,
                'rejection_reason' => $wasRejected ? null : $registration->rejection_reason,
            ]);

            return redirect()
                ->route('athletes.show', $athlete)
                ->with('status', 'Catatan waktu diperbarui.');
        }

        if (! $registration->canChangeEvent()) {
            return back()->withErrors([
                'event_id' => 'Nomor lomba tidak bisa diubah karena sudah masuk seri atau pendaftaran sudah ditutup.',
            ]);
        }

        if ($event->competition_id !== $registration->competition_id) {
            return back()->withInput()->withErrors(['event_id' => 'Nomor lomba bukan milik kejuaraan ini.']);
        }

        $draft = new RegistrationDraft(
            competition: $registration->competition,
            athlete: $athlete,
            event: $event,
            seedTimeInput: $request->validated('seed_time'),
            exceptRegistrationId: $registration->id,
        );

        $errors = $validator->validate($draft);
        if ($errors !== []) {
            return back()
                ->withInput()
                ->withErrors(['registration' => collect($errors)->pluck('message')->implode(' ')]);
        }

        $ageGroup = $ageGroups->resolve($registration->competition, $athlete->birth_year);

        DB::transaction(function () use ($registration, $event, $ms, $ageGroup): void {
            $conflict = Registration::query()
                ->where('event_id', $event->id)
                ->where('athlete_id', $registration->athlete_id)
                ->whereKeyNot($registration->id)
                ->lockForUpdate()
                ->first();

            if ($conflict !== null) {
                $conflict->update([
                    'age_group_id' => $ageGroup?->id ?? $conflict->age_group_id,
                    'seed_time_ms' => $ms,
                    'status' => $registration->status,
                    'rejection_reason' => null,
                    'verified_by' => $registration->verified_by,
                    'verified_at' => $registration->verified_at,
                ]);
                $registration->update(['status' => RegistrationStatus::Withdrawn]);

                return;
            }

            $registration->update([
                'event_id' => $event->id,
                'age_group_id' => $ageGroup?->id ?? $registration->age_group_id,
                'seed_time_ms' => $ms,
            ]);
        });

        return redirect()
            ->route('athletes.show', $athlete)
            ->with('status', 'Nomor lomba diperbarui.');
    }

    public function destroy(Athlete $athlete, Registration $registration): RedirectResponse
    {
        $this->authorize('update', $athlete);
        $this->authorize('delete', $registration);
        $this->assertOwnedByAthlete($athlete, $registration);

        $registration->update(['status' => RegistrationStatus::Withdrawn]);

        return redirect()
            ->route('athletes.show', $athlete)
            ->with('status', 'Nomor lomba dibatalkan.');
    }

    private function assertOwnedByAthlete(Athlete $athlete, Registration $registration): void
    {
        abort_unless($registration->athlete_id === $athlete->id, 404);
    }

    private function persistEntry(
        Competition $competition,
        Athlete $athlete,
        Event $event,
        ?int $ageGroupId,
        ?int $seedTimeMs,
        int $userId,
        bool $verifyNow,
    ): void {
        $payload = [
            'competition_id' => $competition->id,
            'event_id' => $event->id,
            'athlete_id' => $athlete->id,
            'age_group_id' => $ageGroupId,
            'seed_time_ms' => $seedTimeMs,
            'status' => $verifyNow ? RegistrationStatus::Verified : RegistrationStatus::Pending,
            'rejection_reason' => null,
            'registered_by' => $userId,
            'verified_by' => $verifyNow ? $userId : null,
            'verified_at' => $verifyNow ? now() : null,
        ];

        $existing = Registration::query()
            ->where('event_id', $event->id)
            ->where('athlete_id', $athlete->id)
            ->lockForUpdate()
            ->first();

        if ($existing !== null) {
            $existing->update($payload);

            return;
        }

        Registration::query()->create($payload);
    }
}
