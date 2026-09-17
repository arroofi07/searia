<?php

namespace App\Http\Controllers;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\User;
use App\Services\BestSwimmerStanding;
use App\Services\ClubStanding;
use App\Services\MedalTally;
use App\Services\RankingCalculator;
use App\Support\PdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AwardsPdfController extends Controller
{
    public function bestClub(
        Request $request,
        Competition $competition,
        ClubStanding $standing,
        MedalTally $medals,
        RankingCalculator $ranking,
    ): Response {
        $this->authorizeDownload($request, $competition);

        $filename = 'club-terbaik-'.$competition->slug.'.pdf';
        $data = [
            ...$this->headerData($competition),
            'rows' => $standing->forCompetition($competition, $medals, $ranking),
        ];

        return $request->boolean('inline')
            ? PdfRenderer::stream('pdf.best-club', $data, $filename)
            : PdfRenderer::download('pdf.best-club', $data, $filename);
    }

    public function bestSwimmers(
        Request $request,
        Competition $competition,
        BestSwimmerStanding $standing,
        MedalTally $medals,
        RankingCalculator $ranking,
    ): Response {
        $this->authorizeDownload($request, $competition);

        $filename = 'atlet-terbaik-'.$competition->slug.'.pdf';
        $data = [
            ...$this->headerData($competition),
            'groups' => $standing->forCompetition($competition, $medals, $ranking),
        ];

        return $request->boolean('inline')
            ? PdfRenderer::stream('pdf.best-swimmers', $data, $filename, 'a4', 'landscape')
            : PdfRenderer::download('pdf.best-swimmers', $data, $filename, 'a4', 'landscape');
    }

    private function authorizeDownload(Request $request, Competition $competition): void
    {
        if ($this->isStaff($request->user())) {
            return;
        }

        abort_unless($competition->status === CompetitionStatus::Published, 404);
    }

    /**
     * @return array{competitionName: string, venue: string, city: string, dateLabel: string, printedAt: \Illuminate\Support\Carbon, includeCover: bool}
     */
    private function headerData(Competition $competition): array
    {
        $dateLabel = $competition->start_date->equalTo($competition->end_date)
            ? $competition->start_date->translatedFormat('d M Y')
            : $competition->start_date->translatedFormat('d M Y').' – '.$competition->end_date->translatedFormat('d M Y');

        return [
            'competitionName' => $competition->name,
            'venue' => (string) $competition->venue,
            'city' => (string) $competition->city,
            'dateLabel' => $dateLabel,
            'printedAt' => now(),
            'includeCover' => false,
        ];
    }

    private function isStaff(?User $user): bool
    {
        return $user !== null && ($user->isJuri() || $user->managesMasterData());
    }
}
