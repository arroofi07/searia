<?php

namespace App\Http\Controllers\Coach;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Competition;
use App\Services\StartListBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ClubStartListController extends Controller
{
    public function download(Request $request, Competition $competition, Club $club, StartListBuilder $builder): Response
    {
        $user = $request->user();
        abort_unless($user?->isPelatih() && $user->club_id === $club->id, 403);

        $allowed = [
            CompetitionStatus::Seeded,
            CompetitionStatus::Running,
            CompetitionStatus::Finished,
            CompetitionStatus::Published,
        ];
        if (! in_array($competition->status, $allowed, true)) {
            throw new NotFoundHttpException;
        }

        $document = $builder->build($competition, clubId: $club->id);
        $entries = $this->flattenClubEntries($document);

        $pdf = Pdf::loadView('pdf.club-start-list', [
            'document' => $document,
            'club' => $club,
            'entries' => $entries,
            'competitionName' => $document->competitionName,
            'venue' => $document->venue,
            'city' => $document->city,
            'dateLabel' => $document->dateLabel,
            'printedAt' => $document->printedAt,
        ])->setPaper('a4');

        return $pdf->download('start-list-'.$club->id.'-'.$competition->slug.'.pdf');
    }

    /**
     * @return list<array{event_number: int, event_name: string, age_group: string, heat_number: int, lane_number: int, athlete_name: string, seed_time: string}>
     */
    private function flattenClubEntries(\App\DataTransferObjects\StartListDocument $document): array
    {
        $entries = [];

        foreach ($document->sessions as $session) {
            foreach ($session->events as $event) {
                foreach ($event->ageGroups as $ageGroup) {
                    foreach ($ageGroup->heats as $heat) {
                        foreach ($heat->lanes as $lane) {
                            if ($lane->isEmpty()) {
                                continue;
                            }

                            $entries[] = [
                                'event_number' => $event->eventNumber,
                                'event_name' => $event->eventName,
                                'age_group' => $ageGroup->name,
                                'heat_number' => $heat->heatNumber,
                                'lane_number' => $lane->laneNumber,
                                'athlete_name' => (string) $lane->athleteName,
                                'seed_time' => $lane->formattedSeedTime(),
                            ];
                        }
                    }
                }
            }
        }

        return $entries;
    }
}
