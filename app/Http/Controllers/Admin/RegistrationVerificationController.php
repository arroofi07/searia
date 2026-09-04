<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Events\RegistrationsStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkVerifyRegistrationRequest;
use App\Http\Requests\RejectRegistrationRequest;
use App\Models\Club;
use App\Models\Competition;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegistrationVerificationController extends Controller
{
    public function index(Request $request, Competition $competition): View
    {
        $this->authorize('viewAny', Registration::class);

        $registrations = Registration::query()
            ->with(['athlete.club', 'event', 'ageGroup'])
            ->where('competition_id', $competition->id)
            ->where('status', RegistrationStatus::Pending)
            ->when($request->filled('club_id'), function ($query) use ($request): void {
                $query->whereHas('athlete', fn ($athlete) => $athlete->where('club_id', $request->integer('club_id')));
            })
            ->when($request->filled('event_id'), fn ($query) => $query->where('event_id', $request->integer('event_id')))
            ->when($request->filled('age_group_id'), fn ($query) => $query->where('age_group_id', $request->integer('age_group_id')))
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.registrations.index', [
            'competition' => $competition,
            'registrations' => $registrations,
            'clubs' => Club::query()->orderBy('name')->get(),
            'events' => $competition->events,
            'ageGroups' => $competition->ageGroups,
            'filters' => $request->only(['club_id', 'event_id', 'age_group_id']),
        ]);
    }

    public function approve(Registration $registration): RedirectResponse
    {
        $this->authorize('verify', $registration);

        $this->mark($registration->newCollection([$registration]), RegistrationStatus::Verified);

        return back()->with('status', 'Pendaftaran disetujui.');
    }

    public function reject(RejectRegistrationRequest $request, Registration $registration): RedirectResponse
    {
        $registration->update([
            'status' => RegistrationStatus::Rejected,
            'rejection_reason' => $request->validated('rejection_reason'),
            'verified_by' => $request->user()?->id,
            'verified_at' => now(),
        ]);

        event(new RegistrationsStatusUpdated($registration->newCollection([$registration])->fresh(), RegistrationStatus::Rejected));

        return back()->with('status', 'Pendaftaran ditolak.');
    }

    public function bulkApprove(BulkVerifyRegistrationRequest $request, Competition $competition): RedirectResponse
    {
        $registrations = $this->pendingFor($competition, $request->validated('registration_ids'));

        $this->mark($registrations, RegistrationStatus::Verified);

        return back()->with('status', $registrations->count().' pendaftaran disetujui.');
    }

    public function bulkReject(RejectRegistrationRequest $request, Competition $competition): RedirectResponse
    {
        $ids = $request->validated('registration_ids') ?? [];
        $registrations = $this->pendingFor($competition, $ids);
        $reason = $request->validated('rejection_reason');

        DB::transaction(function () use ($registrations, $reason, $request): void {
            foreach ($registrations as $registration) {
                $registration->update([
                    'status' => RegistrationStatus::Rejected,
                    'rejection_reason' => $reason,
                    'verified_by' => $request->user()?->id,
                    'verified_at' => now(),
                ]);
            }
        });

        event(new RegistrationsStatusUpdated($registrations->fresh(), RegistrationStatus::Rejected));

        return back()->with('status', $registrations->count().' pendaftaran ditolak.');
    }

    /**
     * @param  list<int>  $ids
     * @return \Illuminate\Database\Eloquent\Collection<int, Registration>
     */
    private function pendingFor(Competition $competition, array $ids)
    {
        return Registration::query()
            ->with(['athlete.club', 'event', 'registrar'])
            ->where('competition_id', $competition->id)
            ->where('status', RegistrationStatus::Pending)
            ->whereIn('id', $ids)
            ->get();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Registration>  $registrations
     */
    private function mark($registrations, RegistrationStatus $status): void
    {
        $actorId = request()->user()?->id;

        DB::transaction(function () use ($registrations, $status, $actorId): void {
            foreach ($registrations as $registration) {
                $this->authorize('verify', $registration);

                $registration->update([
                    'status' => $status,
                    'rejection_reason' => $status === RegistrationStatus::Verified ? null : $registration->rejection_reason,
                    'verified_by' => $actorId,
                    'verified_at' => now(),
                ]);
            }
        });

        event(new RegistrationsStatusUpdated($registrations->fresh(), $status));
    }
}
