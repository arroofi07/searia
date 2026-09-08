<?php

namespace App\Services;

use App\DataTransferObjects\InvoiceCalculation;
use App\DataTransferObjects\InvoiceLine;
use App\Enums\RegistrationStatus;
use App\Models\Club;
use App\Models\Competition;
use App\Models\Registration;
use App\Models\RegistrationSubmission;
use Illuminate\Support\Collection;

class InvoiceCalculator
{
    /**
     * Hanya entri yang masuk lewat import Excel panitia. Entri dari form publik
     * sudah punya tagihannya sendiri per pengiriman dan tidak boleh ditagih dua kali.
     */
    public function forClub(Competition $competition, Club $club): InvoiceCalculation
    {
        $entries = Registration::query()
            ->with(['athlete', 'event'])
            ->where('competition_id', $competition->id)
            ->where('status', RegistrationStatus::Verified)
            ->whereNull('submission_id')
            ->whereHas('athlete', fn ($athlete) => $athlete->where('club_id', $club->id))
            ->orderBy('id')
            ->get();

        return $this->build($competition, $entries);
    }

    /**
     * Tagihan pendaftaran mandiri terbit saat form dikirim, jadi entrinya masih
     * berstatus `pending`. Entri yang belakangan ditolak panitia dikeluarkan dari
     * tagihan lewat penerbitan ulang.
     */
    public function forSubmission(RegistrationSubmission $submission): InvoiceCalculation
    {
        $entries = Registration::query()
            ->with(['athlete', 'event'])
            ->where('submission_id', $submission->id)
            ->whereNotIn('status', [RegistrationStatus::Rejected, RegistrationStatus::Withdrawn])
            ->orderBy('id')
            ->get();

        return $this->build($submission->competition, $entries);
    }

    /**
     * @param  Collection<int, Registration>  $entries
     */
    private function build(Competition $competition, Collection $entries): InvoiceCalculation
    {
        $closesAt = $competition->registration_closes_at;
        $baseFee = $competition->fee_per_event;
        $lateFeePerEvent = $competition->late_fee_per_event;

        $lines = $entries->map(function (Registration $registration) use ($closesAt, $baseFee, $lateFeePerEvent): InvoiceLine {
            $isLate = $closesAt !== null && $registration->created_at?->gt($closesAt);
            $event = $registration->event;

            return new InvoiceLine(
                registrationId: $registration->id,
                athleteName: (string) $registration->athlete?->full_name,
                eventName: $event === null
                    ? ''
                    : $event->event_number.' '.$event->formattedName(),
                baseFee: $baseFee,
                lateFee: $isLate ? $lateFeePerEvent : 0,
                isLate: (bool) $isLate,
            );
        })->all();

        return new InvoiceCalculation($lines);
    }
}
