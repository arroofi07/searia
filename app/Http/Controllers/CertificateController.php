<?php

namespace App\Http\Controllers;

use App\Actions\GenerateCertificates;
use App\Enums\CompetitionStatus;
use App\Jobs\GenerateCertificateArchive;
use App\Models\Certificate;
use App\Models\CertificateArchive;
use App\Models\Competition;
use App\Services\Certificate\CertificatePdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CertificateController extends Controller
{
    public function index(Competition $competition): View
    {
        abort_unless($competition->status === CompetitionStatus::Published, 404);
        $this->authorize('view', $competition);

        $certificates = Certificate::query()
            ->with(['athlete.club', 'event', 'ageGroup'])
            ->where('competition_id', $competition->id)
            ->orderBy('type')
            ->orderBy('id')
            ->paginate(50);

        return view('admin.certificates.index', [
            'competition' => $competition,
            'certificates' => $certificates,
        ]);
    }

    /**
     * Sertifikat kejuaraan yang sudah dipublikasikan terbuka untuk umum: isinya
     * sama dengan data yang sudah tampil di halaman hasil, dan peserta tidak punya akun.
     */
    public function download(Certificate $certificate, CertificatePdf $pdf): Response
    {
        $certificate->loadMissing(['competition', 'athlete']);

        abort_unless($certificate->competition?->status === CompetitionStatus::Published, 404);

        return $pdf->download($certificate);
    }

    public function requestArchive(Request $request, Competition $competition, GenerateCertificates $generator): RedirectResponse
    {
        abort_unless($competition->status === CompetitionStatus::Published, 403);
        $this->authorize('update', $competition);

        $generator->handle($competition);

        $archive = CertificateArchive::query()->create([
            'competition_id' => $competition->id,
            'requested_by' => $request->user()?->id,
            'club_id' => $request->filled('club_id') ? $request->integer('club_id') : null,
            'status' => 'pending',
        ]);

        GenerateCertificateArchive::dispatch($archive->id);

        return back()->with('status', 'Arsip sertifikat sedang disiapkan. Anda akan mendapat pemberitahuan saat siap.');
    }

    public function downloadArchive(string $token): BinaryFileResponse
    {
        $archive = CertificateArchive::query()->where('token', $token)->firstOrFail();

        abort_unless($archive->isReady() && ! $archive->isExpired(), 404);
        abort_unless($archive->disk_path !== null && Storage::disk('local')->exists($archive->disk_path), 404);

        return response()->download(
            Storage::disk('local')->path($archive->disk_path),
            'sertifikat-'.$archive->competition_id.'.zip',
        );
    }

    public function settings(Request $request, Competition $competition): RedirectResponse
    {
        $this->authorize('update', $competition);

        $data = $request->validate([
            'certificate_signer_name' => ['nullable', 'string', 'max:120'],
            'certificate_signer_title' => ['nullable', 'string', 'max:120'],
            'certificate_background' => ['nullable', 'image', 'max:4096'],
        ]);

        $payload = [
            'certificate_signer_name' => $data['certificate_signer_name'] ?? null,
            'certificate_signer_title' => $data['certificate_signer_title'] ?? null,
        ];

        if ($request->hasFile('certificate_background')) {
            $payload['certificate_background_path'] = $request->file('certificate_background')
                ->store('certificates/backgrounds', 'public');
        }

        $competition->update($payload);

        return back()->with('status', 'Pengaturan sertifikat disimpan.');
    }
}
