<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Registration;
use App\Services\AgeGroupOverride;
use Illuminate\View\View;

class AgeGroupPromotionController extends Controller
{
    public function index(Competition $competition, AgeGroupOverride $overrides): View
    {
        $this->authorize('viewAny', Registration::class);

        $registrations = Registration::query()
            ->where('competition_id', $competition->id)
            ->whereNot('status', RegistrationStatus::Withdrawn)
            ->with(['athlete.club', 'event.ageGroups', 'ageGroup'])
            ->orderBy('id')
            ->get();

        $promoted = $registrations
            ->filter(fn (Registration $registration): bool => $registration->isAgeGroupOverride())
            ->values();

        $candidates = $registrations
            ->reject(fn (Registration $registration): bool => $registration->isAgeGroupOverride())
            ->filter(function (Registration $registration) use ($competition, $overrides): bool {
                $athlete = $registration->athlete;
                $event = $registration->event;

                return $athlete !== null
                    && $event !== null
                    && $overrides->nearestOlderEligible($competition, $athlete, $event) !== null;
            })
            ->values();

        return view('admin.age-group-promotions.index', [
            'competition' => $competition,
            'promoted' => $promoted,
            'candidates' => $candidates,
            'ageGroups' => $competition->ageGroups,
            'overrides' => $overrides,
        ]);
    }
}
