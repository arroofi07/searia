<?php

namespace App\Http\Controllers;

use App\Enums\CompetitionStatus;
use App\Models\Club;
use App\Models\Competition;
use App\Services\StartListBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StartListController extends Controller
{
    public function show(Request $request, Competition $competition, StartListBuilder $builder): View
    {
        $this->ensurePubliclyVisible($competition);

        $document = $builder->build(
            competition: $competition,
            eventId: $request->filled('event_id') ? $request->integer('event_id') : null,
            ageGroupId: $request->filled('age_group_id') ? $request->integer('age_group_id') : null,
            clubId: $request->filled('club_id') ? $request->integer('club_id') : null,
        );

        $search = trim((string) $request->query('q', ''));

        return view('start-list.show', [
            'competition' => $competition,
            'document' => $document,
            'events' => $competition->events()->orderBy('session')->orderBy('sort_order')->orderBy('event_number')->get(),
            'ageGroups' => $competition->ageGroups()->orderBy('sort_order')->get(),
            'clubs' => Club::query()->orderBy('name')->get(),
            'filters' => [
                'event_id' => $request->query('event_id'),
                'age_group_id' => $request->query('age_group_id'),
                'club_id' => $request->query('club_id'),
                'q' => $search,
            ],
            'search' => $search,
        ]);
    }

    private function ensurePubliclyVisible(Competition $competition): void
    {
        $allowed = [
            CompetitionStatus::Seeded,
            CompetitionStatus::Running,
            CompetitionStatus::Finished,
            CompetitionStatus::Published,
        ];

        if (! in_array($competition->status, $allowed, true)) {
            throw new NotFoundHttpException;
        }
    }
}
