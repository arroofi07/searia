<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Registration;
use App\Models\RegistrationSubmission;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Daftar pendaftaran mandiri beserta kontak pendaftarnya. Inilah tempat panitia
 * mencari nomor telepon yang harus dihubungi ketika sebuah entri bermasalah.
 */
class SubmissionController extends Controller
{
    public function index(Request $request, Competition $competition): View
    {
        $this->authorize('viewAny', Registration::class);

        $submissions = RegistrationSubmission::query()
            ->with(['athlete.club', 'registrations.event'])
            ->where('competition_id', $competition->id)
            ->when($request->filled('code'), fn ($query) => $query->where('code', $request->string('code')->upper()->toString()))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.mb_strtolower((string) $request->string('search')).'%';
                $query->where(function ($inner) use ($search): void {
                    $inner->whereRaw('LOWER(registrant_name) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(registrant_phone) LIKE ?', [$search])
                        ->orWhereHas('athlete', fn ($athlete) => $athlete->whereRaw('LOWER(full_name) LIKE ?', [$search]));
                });
            })
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.submissions.index', [
            'competition' => $competition,
            'submissions' => $submissions,
            'filters' => $request->only(['code', 'search']),
        ]);
    }

    public function show(RegistrationSubmission $submission): View
    {
        $this->authorize('viewAny', Registration::class);

        $submission->load(['competition', 'athlete.club', 'registrations.event', 'registrations.ageGroup']);

        return view('admin.submissions.show', [
            'submission' => $submission,
            'competition' => $submission->competition,
        ]);
    }
}
