<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Exceptions\InvalidSwimTimeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManualRegistrationRequest;
use App\Http\Requests\UpdateRegistrationRequest;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Registration;
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
        ]);
    }

    public function store(
        StoreManualRegistrationRequest $request,
        Competition $competition,
        RegistrationValidator $validator,
        AgeGroupResolver $ageGroups,
    ): RedirectResponse {
        $this->authorize('create', [Registration::class, $competition]);

        $athlete = Athlete::query()->findOrFail($request->integer('athlete_id'));
        $event = Event::query()
            ->where('competition_id', $competition->id)
            ->whereKey($request->integer('event_id'))
            ->firstOrFail();

        $draft = new RegistrationDraft(
            competition: $competition,
            athlete: $athlete,
            event: $event,
            seedTimeInput: $request->validated('seed_time'),
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
        $verifyNow = $request->boolean('verify_now');

        Registration::query()->create([
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

        return redirect()
            ->route('admin.registrations.index', $competition)
            ->with('status', $verifyNow
                ? 'Entri ditambahkan dan langsung diverifikasi.'
                : 'Entri ditambahkan ke antrean verifikasi.');
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
