<?php

namespace App\Services;

use App\DataTransferObjects\InvoiceCalculation;
use App\DataTransferObjects\InvoiceLine;
use App\Enums\RegistrationStatus;
use App\Models\Club;
use App\Models\Competition;
use App\Models\Registration;

class InvoiceCalculator
{
    public function forClub(Competition $competition, Club $club): InvoiceCalculation
    {
        $entries = Registration::query()
            ->with(['athlete', 'event'])
            ->where('competition_id', $competition->id)
            ->where('status', RegistrationStatus::Verified)
            ->whereHas('athlete', fn ($athlete) => $athlete->where('club_id', $club->id))
            ->orderBy('id')
            ->get();

        $closesAt = $competition->registration_closes_at;
        $baseFee = $competition->fee_per_event;
        $lateFeePerEvent = $competition->late_fee_per_event;

        $lines = $entries->map(function (Registration $registration) use ($closesAt, $baseFee, $lateFeePerEvent): InvoiceLine {
            $isLate = $closesAt !== null && $registration->created_at?->gt($closesAt);
            $lateFee = $isLate ? $lateFeePerEvent : 0;
            $event = $registration->event;

            return new InvoiceLine(
                registrationId: $registration->id,
                athleteName: (string) $registration->athlete?->full_name,
                eventName: $event === null
                    ? ''
                    : $event->event_number.' '.$event->formattedName(),
                baseFee: $baseFee,
                lateFee: $lateFee,
                isLate: (bool) $isLate,
            );
        })->all();

        return new InvoiceCalculation($lines);
    }
}
