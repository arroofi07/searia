<?php

namespace App\Http\Controllers;

use App\Enums\CompetitionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller as BaseController;
use App\Models\Competition;
use App\Services\ResultsBookBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ResultsPdfController extends BaseController
{
    public function download(Request $request, Competition $competition, ResultsBookBuilder $builder): Response
    {
        $user = $request->user();
        $isStaff = $user !== null && in_array($user->role, [UserRole::SuperAdmin, UserRole::Panitia, UserRole::Juri], true);

        if (! $isStaff) {
            abort_unless($competition->status === CompetitionStatus::Published, 404);
        } else {
            abort_unless($competition->status->isSeededOrLater(), 404);
        }

        $document = $builder->build(
            competition: $competition,
            session: $request->filled('session') ? $request->integer('session') : null,
            eventId: $request->filled('event_id') ? $request->integer('event_id') : null,
        );

        $pdf = Pdf::loadView('pdf.results-book', [
            'document' => $document,
            'competitionName' => $document->competitionName,
            'venue' => $document->venue,
            'city' => $document->city,
            'dateLabel' => $document->dateLabel,
            'printedAt' => $document->printedAt,
        ])->setPaper('a4');

        return $pdf->download('hasil-lomba-'.$competition->slug.'.pdf');
    }
}
