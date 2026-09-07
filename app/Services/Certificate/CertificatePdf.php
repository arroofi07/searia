<?php

namespace App\Services\Certificate;

use App\Models\Certificate;
use App\Support\SwimTime;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class CertificatePdf
{
    public function download(Certificate $certificate): Response
    {
        $certificate->loadMissing(['competition', 'athlete.club', 'event', 'ageGroup']);

        $view = $certificate->isWinner()
            ? 'pdf.certificate-winner'
            : 'pdf.certificate-participant';

        $pdf = Pdf::loadView($view, $this->viewData($certificate))->setPaper('a4', 'landscape');

        $slug = $certificate->isWinner() ? 'juara' : 'peserta';

        return $pdf->download($slug.'-'.$certificate->code.'.pdf');
    }

    public function render(Certificate $certificate): string
    {
        $certificate->loadMissing(['competition', 'athlete.club', 'event', 'ageGroup']);

        $view = $certificate->isWinner()
            ? 'pdf.certificate-winner'
            : 'pdf.certificate-participant';

        return Pdf::loadView($view, $this->viewData($certificate))
            ->setPaper('a4', 'landscape')
            ->output();
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(Certificate $certificate): array
    {
        $competition = $certificate->competition;
        $athlete = $certificate->athlete;
        $event = $certificate->event;
        $verifyUrl = $certificate->verificationUrl();

        return [
            'certificate' => $certificate,
            'competition' => $competition,
            'athleteName' => (string) $athlete?->full_name,
            'clubName' => (string) ($athlete?->club?->name ?? ''),
            'city' => (string) ($athlete?->club?->city ?? ''),
            'eventLabel' => $event !== null
                ? 'Acara '.$event->event_number.' · '.$event->formattedName()
                : '',
            'ageGroupName' => (string) ($certificate->ageGroup?->name ?? ''),
            'timeLabel' => SwimTime::formatMilliseconds($certificate->time_ms),
            'rank' => $certificate->rank,
            'statusLabel' => $certificate->status?->label() ?? '',
            'signerName' => $competition?->certificate_signer_name ?: config('searia.certificate.signer_name', 'Panitia'),
            'signerTitle' => $competition?->certificate_signer_title ?: config('searia.certificate.signer_title', 'Ketua Panitia'),
            'backgroundPath' => $this->backgroundUrl($competition?->certificate_background_path),
            'verificationUrl' => $verifyUrl,
            'qrUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data='.urlencode($verifyUrl),
            'code' => $certificate->code,
        ];
    }

    private function backgroundUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $absolute = storage_path('app/public/'.$path);
        if (! is_file($absolute)) {
            return null;
        }

        return 'file://'.str_replace('\\', '/', $absolute);
    }
}
