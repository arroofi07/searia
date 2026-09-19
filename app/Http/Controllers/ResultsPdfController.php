<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Competition;
use App\Models\User;
use App\Services\ResultsBookBuilder;
use App\Support\PdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ResultsPdfController extends BaseController
{
    public function download(Request $request, Competition $competition, ResultsBookBuilder $builder): Response
    {
        $user = $request->user();

        if (! $this->isStaff($user)) {
            abort_unless($competition->hasPublicResults(), 404);
        }

        $document = $builder->build(
            competition: $competition,
            session: $request->filled('session') ? $request->integer('session') : null,
            eventId: $request->filled('event_id') ? $request->integer('event_id') : null,
        );

        $filename = 'buku-hasil-'.$competition->slug.'.pdf';
        $data = [
            'document' => $document,
            'competitionName' => $document->competitionName,
            'venue' => $document->venue,
            'city' => $document->city,
            'dateLabel' => $document->dateLabel,
            'printedAt' => $document->printedAt,
            'includeCover' => ! $request->filled('event_id'),
        ];

        $response = $request->boolean('inline')
            ? PdfRenderer::stream('pdf.results-book', $data, $filename)
            : PdfRenderer::download('pdf.results-book', $data, $filename);

        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    private function isStaff(?User $user): bool
    {
        return $user !== null && ($user->isJuri() || $user->managesMasterData());
    }
}
