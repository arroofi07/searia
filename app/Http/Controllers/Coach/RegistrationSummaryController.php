<?php

namespace App\Http\Controllers\Coach;

use App\Enums\CompetitionStatus;
use App\Enums\RegistrationStatus;
use App\Exceptions\InvalidSwimTimeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRegistrationRequest;
use App\Models\Competition;
use App\Models\Registration;
use App\Support\SwimTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationSummaryController extends Controller
{
    public function index(Request $request, Competition $competition): View
    {
        $this->authorize('viewAny', Registration::class);

        $query = Registration::query()
            ->with(['athlete', 'event', 'ageGroup'])
            ->where('competition_id', $competition->id)
            ->when(
                $request->user()?->cannot('manageAny', \App\Models\Athlete::class),
                fn ($builder) => $builder->whereHas(
                    'athlete',
                    fn ($athlete) => $athlete->where('club_id', $request->user()?->club_id),
                ),
            )
            ->orderBy('athlete_id');

        $registrations = $query->get();
        $grouped = $registrations->groupBy(fn (Registration $registration): int => $registration->athlete_id);
        $runningTotal = $registrations
            ->filter(fn (Registration $registration): bool => $registration->status !== RegistrationStatus::Withdrawn)
            ->count() * $competition->fee_per_event;

        return view('coach.registrations.index', [
            'competition' => $competition,
            'grouped' => $grouped,
            'runningTotal' => $runningTotal,
            'locked' => $competition->status !== CompetitionStatus::Registration,
        ]);
    }

    public function update(UpdateRegistrationRequest $request, Registration $registration): RedirectResponse
    {
        try {
            $ms = SwimTime::parse($request->validated('seed_time'))?->milliseconds;
        } catch (InvalidSwimTimeException $exception) {
            return back()->withErrors(['seed_time' => $exception->getMessage()]);
        }

        $registration->update([
            'seed_time_ms' => $ms,
            'status' => $registration->status === RegistrationStatus::Rejected
                ? RegistrationStatus::Pending
                : $registration->status,
            'rejection_reason' => $registration->status === RegistrationStatus::Rejected
                ? null
                : $registration->rejection_reason,
        ]);

        return back()->with('status', 'Catatan waktu diperbarui.');
    }

    public function destroy(Registration $registration): RedirectResponse
    {
        $this->authorize('delete', $registration);

        $registration->update([
            'status' => RegistrationStatus::Withdrawn,
        ]);

        return back()->with('status', 'Entri dibatalkan.');
    }
}
