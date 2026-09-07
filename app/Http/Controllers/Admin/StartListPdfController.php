<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Services\StartListBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class StartListPdfController extends Controller
{
    public function index(Competition $competition): View
    {
        $this->authorize('seed', $competition);

        return view('admin.start-list.index', [
            'competition' => $competition,
            'events' => $competition->events,
            'sessions' => $competition->events->pluck('session')->unique()->sort()->values(),
        ]);
    }

    public function book(Request $request, Competition $competition, StartListBuilder $builder): Response
    {
        $this->authorize('seed', $competition);

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

        return $pdf->download($this->filename($competition, 'buku-acara'));
    }

    public function resultSheets(Request $request, Competition $competition, StartListBuilder $builder): Response
    {
        $this->authorize('seed', $competition);

        $document = $builder->build(
            competition: $competition,
            session: $request->filled('session') ? $request->integer('session') : null,
            eventId: $request->filled('event_id') ? $request->integer('event_id') : null,
        );

        $pdf = Pdf::loadView('pdf.result-sheet', [
            'document' => $document,
            'competitionName' => $document->competitionName,
            'venue' => $document->venue,
            'city' => $document->city,
            'dateLabel' => $document->dateLabel,
            'printedAt' => $document->printedAt,
        ])->setPaper('a4');

        return $pdf->download($this->filename($competition, 'lembar-hasil'));
    }

    private function filename(Competition $competition, string $prefix): string
    {
        return $prefix.'-'.$competition->slug.'.pdf';
    }
}
