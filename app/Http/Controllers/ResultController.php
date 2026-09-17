<?php

namespace App\Http\Controllers;

use App\Enums\CompetitionStatus;
use App\Models\ActivityLog;
use App\Models\AgeGroup;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Result;
use App\Services\BestSwimmerStanding;
use App\Services\ClubStanding;
use App\Services\MedalTally;
use App\Services\RankingCalculator;
use App\Support\ListPaginator;
use App\Support\SwimTime;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResultController extends Controller
{
    public function index(Request $request, Competition $competition, RankingCalculator $ranking): View
    {
        $this->authorizePublicOrPreview($request, $competition);

        $events = $competition->events()
            ->with(['ageGroups', 'heats.ageGroup'])
            ->orderBy('session')
            ->orderBy('sort_order')
            ->orderBy('event_number')
            ->get();
        $eventBlocks = [];

        foreach ($events as $event) {
            $groups = $event->heats
                ->map(function ($heat) use ($event) {
                    return $heat->ageGroup
                        ?? $event->ageGroups->firstWhere('id', $heat->age_group_id);
                })
                ->filter()
                ->unique('id')
                ->sortBy(fn (AgeGroup $group): int => (int) $group->sort_order)
                ->values();

            foreach ($groups as $ageGroup) {
                $table = $ranking->forEventAgeGroup($event, $ageGroup);
                if ($table->entries === []) {
                    continue;
                }

                $eventBlocks[] = $table;
            }
        }

        $tables = ListPaginator::for($eventBlocks);

        return view('results.index', [
            'competition' => $competition,
            'tables' => $tables,
            'preview' => $competition->status !== CompetitionStatus::Published,
            'formatTime' => fn (?int $ms): string => SwimTime::formatMilliseconds($ms),
        ]);
    }

    public function show(Request $request, Competition $competition, Event $event, AgeGroup $ageGroup, RankingCalculator $ranking): View
    {
        $this->authorizePublicOrPreview($request, $competition);
        abort_unless($event->competition_id === $competition->id, 404);
        abort_unless($ageGroup->competition_id === $competition->id, 404);

        $table = $ranking->forEventAgeGroup($event, $ageGroup);
        $resultIds = collect($table->entries)->pluck('resultId')->filter()->all();

        $correctionLogs = ActivityLog::query()
            ->with('user')
            ->where('action', 'result.correct')
            ->where('subject_type', Result::class)
            ->whereIn('subject_id', $resultIds)
            ->orderByDesc('created_at')
            ->get();

        return view('results.show', [
            'competition' => $competition,
            'event' => $event,
            'ageGroup' => $ageGroup,
            'table' => $table,
            'entries' => ListPaginator::for($table->entries),
            'correctionLogs' => ListPaginator::for($correctionLogs, pageName: 'correction_page'),
            'preview' => $competition->status !== CompetitionStatus::Published,
            'formatTime' => fn (?int $ms): string => SwimTime::formatMilliseconds($ms),
        ]);
    }

    public function medals(Request $request, Competition $competition, MedalTally $medals, RankingCalculator $ranking): View
    {
        $this->authorizePublicOrPreview($request, $competition);

        $blocks = $medals->forCompetition($competition->load('events'), $ranking);

        return view('results.medals', [
            'competition' => $competition,
            'blocks' => ListPaginator::for($blocks),
            'byClub' => ListPaginator::for($medals->rollupClubs($blocks), pageName: 'club_page'),
            'byAgeGroup' => ListPaginator::for($medals->rollupAgeGroups($blocks), pageName: 'group_page'),
            'preview' => $competition->status !== CompetitionStatus::Published,
            'formatTime' => fn (?int $ms): string => SwimTime::formatMilliseconds($ms),
        ]);
    }

    public function standings(Request $request, Competition $competition, ClubStanding $standing, MedalTally $medals, RankingCalculator $ranking): View
    {
        $this->authorizePublicOrPreview($request, $competition);

        return view('results.standings', [
            'competition' => $competition,
            'rows' => ListPaginator::for($standing->forCompetition($competition, $medals, $ranking)),
            'preview' => $competition->status !== CompetitionStatus::Published,
        ]);
    }

    public function bestClub(Request $request, Competition $competition, ClubStanding $standing, MedalTally $medals, RankingCalculator $ranking): View
    {
        $this->authorizePublicOrPreview($request, $competition);

        return view('results.best-club', [
            'competition' => $competition,
            'rows' => ListPaginator::for($standing->forCompetition($competition, $medals, $ranking)),
            'preview' => $competition->status !== CompetitionStatus::Published,
        ]);
    }

    public function bestSwimmers(Request $request, Competition $competition, BestSwimmerStanding $standing, MedalTally $medals, RankingCalculator $ranking): View
    {
        $this->authorizePublicOrPreview($request, $competition);

        return view('results.best-swimmers', [
            'competition' => $competition,
            'groups' => ListPaginator::for($standing->forCompetition($competition, $medals, $ranking)),
            'preview' => $competition->status !== CompetitionStatus::Published,
        ]);
    }

    private function authorizePublicOrPreview(Request $request, Competition $competition): void
    {
        if ($competition->status === CompetitionStatus::Published) {
            return;
        }

        $user = $request->user();
        if ($user?->managesMasterData() && in_array($competition->status, [
            CompetitionStatus::Finished,
            CompetitionStatus::Running,
            CompetitionStatus::Seeded,
        ], true)) {
            return;
        }

        throw new NotFoundHttpException;
    }
}
