<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Support\SwimTime;
use Illuminate\View\View;

class CertificateVerificationController extends Controller
{
    public function show(string $code): View
    {
        $certificate = Certificate::query()
            ->with(['competition', 'athlete.club', 'event', 'ageGroup'])
            ->where('code', strtoupper($code))
            ->first();

        if ($certificate === null) {
            return view('public.certificates.verify', [
                'found' => false,
                'code' => $code,
            ]);
        }

        return view('public.certificates.verify', [
            'found' => true,
            'code' => $certificate->code,
            'athleteName' => $certificate->athlete?->full_name,
            'clubName' => $certificate->athlete?->club?->name,
            'competitionName' => $certificate->competition?->name,
            'eventLabel' => $certificate->event !== null
                ? 'Acara '.$certificate->event->event_number.' · '.$certificate->event->formattedName()
                : null,
            'ageGroupName' => $certificate->ageGroup?->name,
            'timeLabel' => SwimTime::formatMilliseconds($certificate->time_ms),
            'rank' => $certificate->rank,
            'type' => $certificate->type,
            'statusLabel' => $certificate->status?->label(),
        ]);
    }
}
