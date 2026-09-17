<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Exceptions\CannotOverrideAgeGroupException;
use App\Exceptions\InvalidSwimTimeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\OverrideAgeGroupRequest;
use App\Http\Requests\StoreManualRegistrationRequest;
use App\Http\Requests\UpdateRegistrationRequest;
use App\Models\ActivityLog;
use App\Models\AgeGroup;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Registration;
use App\Services\AgeGroupOverride;
use App\Services\AgeGroupResolver;
use App\Services\RegistrationDraft;
use App\Services\RegistrationValidator;
use App\Support\SwimTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Input dan koreksi entri oleh panitia (manual, tanpa wizard publik).
 */
class RegistrationEntryController extends Controller
{
    public function create(Competition $competition): View
    {
        $this->authorize('create', [Registration::class, $competition]);

        return view('admin.registrations.create', [
            'competition' => $competition,
            'athletes' => Athlete::query()->with('club')->where('is_active', true)->orderBy('full_name')->get(),
            'events' => $competition->events()->orderBy('sort_order')->orderBy('event_number')->get(),
            'ageGroups' => $competition->ageGroups,
        ]);
    }

    public function store(
        StoreManualRegistrationRequest $request,
        Competition $competition,
        RegistrationValidator $validator,
        AgeGroupResolver $ageGroups,
        AgeGroupOverride $overrides,
    ): RedirectResponse {
        $this->authorize('create', [Registration::class, $competition]);

        $athlete = Athlete::query()->findOrFail($request->integer('athlete_id'));
        $event = Event::query()
            ->where('competition_id', $competition->id)
            ->whereKey($request->integer('event_id'))
            ->firstOrFail();

        $overrideGroup = null;
        if ($request->filled('age_group_id')) {
            $overrideGroup = AgeGroup::query()
                ->where('competition_id', $competition->id)
                ->whereKey($request->integer('age_group_id'))
                ->first();

            if ($overrideGroup === null) {
                return back()->withInput()->withErrors(['age_group_id' => 'Kelompok umur bukan milik kejuaraan ini.']);
            }
        }

        $draft = new RegistrationDraft(
            competition: $competition,
            athlete: $athlete,
            event: $event,
            seedTimeInput: $request->validated('seed_time'),
            ageGroupOverride: $overrideGroup,
            overrideReason: $request->validated('override_reason'),
        );

        $errors = $validator->validate($draft);
        if ($errors !== []) {
            return back()
                ->withInput()
                ->withErrors(['registration' => collect($errors)->pluck('message')->implode(' ')]);
        }

        try {
            $ms = SwimTime::parse($request->validated('seed_time'))?->milliseconds;
        } catch (InvalidSwimTimeException $exception) {
            return back()->withInput()->withErrors(['seed_time' => $exception->getMessage()]);
        }

        $ageGroup = $ageGroups->resolve($competition, $athlete->birth_year);
        $natural = $ageGroup;

        if ($overrideGroup !== null) {
            try {
                $ageGroup = $overrides->decide($competition, $athlete, $event, $overrideGroup);
            } catch (CannotOverrideAgeGroupException $exception) {
                return back()->withInput()->withErrors(['age_group_id' => $exception->getMessage()]);
            }
        }

        $verifyNow = $request->boolean('verify_now');

        $registration = Registration::query()->create([
            'competition_id' => $competition->id,
            'event_id' => $event->id,
            'athlete_id' => $athlete->id,
            'age_group_id' => $ageGroup?->id,
            'seed_time_ms' => $ms,
            'status' => $verifyNow ? RegistrationStatus::Verified : RegistrationStatus::Pending,
            'registered_by' => $request->user()->id,
            'verified_by' => $verifyNow ? $request->user()->id : null,
            'verified_at' => $verifyNow ? now() : null,
        ]);

        if ($ageGroup !== null && $natural !== null && ! $ageGroup->is($natural)) {
            ActivityLog::record(
                $request->user(),
                'registration.age_group_override',
                $registration,
                [
                    'age_group_id' => $natural->id,
                    'age_group_name' => $natural->name,
                ],
                [
                    'age_group_id' => $ageGroup->id,
                    'age_group_name' => $ageGroup->name,
                ],
                trim((string) $request->validated('override_reason')),
                $request->ip(),
            );
        }

        return redirect()
            ->route('admin.registrations.index', $competition)
            ->with('status', $verifyNow
                ? 'Entri ditambahkan dan langsung diverifikasi.'
                : 'Entri ditambahkan ke antrean verifikasi.');
    }

    public function overrideAgeGroup(
        OverrideAgeGroupRequest $request,
        Registration $registration,
        AgeGroupOverride $overrides,
    ): RedirectResponse {
        $this->authorize('update', $registration);

        $target = AgeGroup::query()
            ->where('competition_id', $registration->competition_id)
            ->whereKey($request->integer('age_group_id'))
            ->first();

        if ($target === null) {
            return back()->withInput()->withErrors(['age_group_id' => 'Kelompok umur bukan milik kejuaraan ini.']);
        }

        try {
            $overrides->apply(
                $registration,
                $target,
                $request->user(),
                $request->validated('reason'),
                $request->ip(),
            );
        } catch (CannotOverrideAgeGroupException $exception) {
            return back()->withInput()->withErrors(['age_group_id' => $exception->getMessage()]);
        }

        return back()->with('status', 'Kelompok umur diperbarui.');
    }

    public function update(UpdateRegistrationRequest $request, Registration $registration): RedirectResponse
    {
        try {
            $ms = SwimTime::parse($request->validated('seed_time'))?->milliseconds;
        } catch (InvalidSwimTimeException $exception) {
            return back()->withErrors(['seed_time' => $exception->getMessage()]);
        }

        $wasRejected = $registration->status === RegistrationStatus::Rejected;

        $registration->update([
            'seed_time_ms' => $ms,
            'status' => $wasRejected ? RegistrationStatus::Pending : $registration->status,
            'rejection_reason' => $wasRejected ? null : $registration->rejection_reason,
        ]);

        return back()->with('status', 'Catatan waktu diperbarui.');
    }

    public function destroy(Registration $registration): RedirectResponse
    {
        $this->authorize('delete', $registration);

        $registration->update(['status' => RegistrationStatus::Withdrawn]);

        return back()->with('status', 'Entri dibatalkan.');
    }
}
