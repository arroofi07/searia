<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\User;
use App\Services\StartListBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StartListPdfDownloadController extends Controller
{
    public function download(Request $request, Competition $competition, StartListBuilder $builder): Response
    {
        if (! $this->isStaff($request->user())) {
            abort_unless($competition->status->isSeededOrLater(), 404);
        }

        $document = $builder->build(
            competition: $competition,
            session: $request->filled('session') ? $request->integer('session') : null,
            eventId: $request->filled('event_id') ? $request->integer('event_id') : null,
        );

        $filename = 'buku-acara-'.$competition->slug.'.pdf';
        $pdf = Pdf::loadView('pdf.start-list', [
            'document' => $document,
            'competitionName' => $document->competitionName,
            'venue' => $document->venue,
            'city' => $document->city,
            'dateLabel' => $document->dateLabel,
            'printedAt' => $document->printedAt,
            'includeToc' => ! $request->filled('event_id'),
            'includeCover' => ! $request->filled('event_id'),
        ])->setPaper('a4');

        return $request->boolean('inline')
            ? $pdf->stream($filename)
            : $pdf->download($filename);
    }

    private function isStaff(?User $user): bool
    {
        return $user !== null && ($user->isJuri() || $user->managesMasterData());
    }
}
