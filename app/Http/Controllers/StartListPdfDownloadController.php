<?php

namespace App\Http\Controllers;

use App\Enums\CompetitionStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Services\StartListBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StartListPdfDownloadController extends Controller
{
    public function download(Request $request, Competition $competition, StartListBuilder $builder): Response
    {
        $user = $request->user();
        $isStaff = $user !== null && in_array($user->role, [UserRole::SuperAdmin, UserRole::Panitia, UserRole::Juri], true);

        if (! $isStaff) {
            abort_unless($competition->status->isSeededOrLater(), 404);
        } else {
            abort_unless($competition->status->isSeededOrLater(), 404);
        }

        $document = $builder->build(
            competition: $competition,
            session: $request->filled('session') ? $request->integer('session') : null,
            eventId: $request->filled('event_id') ? $request->integer('event_id') : null,
        );

        $pdf = Pdf::loadView('pdf.start-list', [
            'document' => $document,
            'competitionName' => $document->competitionName,
            'venue' => $document->venue,
            'city' => $document->city,
            'dateLabel' => $document->dateLabel,
            'printedAt' => $document->printedAt,
            'includeToc' => ! $request->filled('event_id'),
        ])->setPaper('a4');

        return $pdf->download('buku-acara-'.$competition->slug.'.pdf');
    }
}
