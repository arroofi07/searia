<?php

namespace App\Jobs;

use App\Actions\GenerateCertificates;
use App\Models\Certificate;
use App\Models\CertificateArchive;
use App\Notifications\CertificateArchiveReady;
use App\Services\Certificate\CertificatePdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class GenerateCertificateArchive implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $archiveId) {}

    public function handle(GenerateCertificates $generator, CertificatePdf $pdf): void
    {
        $archive = CertificateArchive::query()->with(['competition', 'requester', 'club'])->find($this->archiveId);
        if ($archive === null) {
            return;
        }

        $archive->update(['status' => 'processing']);

        try {
            $competition = $archive->competition;
            if ($competition === null) {
                throw new \RuntimeException('Kejuaraan tidak ditemukan.');
            }

            $generator->handle($competition);

            $query = Certificate::query()
                ->with(['athlete.club', 'event', 'ageGroup', 'competition'])
                ->where('competition_id', $competition->id);

            if ($archive->club_id !== null) {
                $query->whereHas('athlete', fn ($q) => $q->where('club_id', $archive->club_id));
            }

            $certificates = $query->orderBy('type')->orderBy('id')->get();

            $relativeDir = 'certificate-archives/'.$competition->id;
            Storage::disk('local')->makeDirectory($relativeDir);
            $zipName = 'sertifikat-'.$competition->slug.'-'.$archive->token.'.zip';
            $relativePath = $relativeDir.'/'.$zipName;
            $absoluteZip = Storage::disk('local')->path($relativePath);

            $zip = new ZipArchive;
            if ($zip->open($absoluteZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Gagal membuat arsip ZIP.');
            }

            foreach ($certificates as $certificate) {
                $prefix = $certificate->isWinner() ? 'juara' : 'peserta';
                $athlete = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $certificate->athlete?->full_name) ?: 'atlet';
                $filename = $prefix.'-'.$athlete.'-'.$certificate->code.'.pdf';
                $zip->addFromString($filename, $pdf->render($certificate));
            }

            $zip->close();

            $archive->update([
                'disk_path' => $relativePath,
                'status' => 'ready',
                'ready_at' => now(),
                'expires_at' => now()->addDays(7),
            ]);

            $archive->requester?->notify(new CertificateArchiveReady($archive->fresh()));
        } catch (\Throwable $exception) {
            $archive->update(['status' => 'failed']);

            throw $exception;
        }
    }
}
