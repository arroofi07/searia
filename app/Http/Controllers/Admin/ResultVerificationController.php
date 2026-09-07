<?php

namespace App\Http\Controllers\Admin;

use App\Actions\VerifyHeatResults;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\Result;
use App\Services\ResultAnomalyDetector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultVerificationController extends Controller
{
    public function index(Competition $competition, ResultAnomalyDetector $detector): View
    {
        $this->authorize('seed', $competition);

        $heats = Heat::query()
            ->whereHas('event', fn ($q) => $q->where('competition_id', $competition->id))
            ->with([
                'event',
                'ageGroup',
                'lanes.result',
                'lanes.registration',
            ])
            ->orderBy('event_id')
            ->orderBy('age_group_id')
            ->orderBy('heat_number')
            ->get();

        $rows = $heats->map(function (Heat $heat) use ($detector) {
            $results = $heat->lanes->map->result->filter();
            $unverified = $results->filter(fn (?Result $result) => $result && $result->verified_at === null)->count();

            return [
                'heat' => $heat,
                'result_count' => $results->count(),
                'unverified' => $unverified,
                'locked' => $heat->isResultsLocked(),
                'anomalies' => $detector->forHeat($heat),
            ];
        });

        return view('admin.results.verify', [
            'competition' => $competition,
            'rows' => $rows,
            'pendingHeats' => $rows->filter(fn (array $row) => $row['unverified'] > 0),
        ]);
    }

    public function verifyHeat(Request $request, Heat $heat, VerifyHeatResults $verifier): RedirectResponse
    {
        $heat->loadMissing('event.competition');
        $this->authorize('seed', $heat->event->competition);

        $count = $verifier->handleHeat($heat, $request->user());

        return back()->with('status', $count.' hasil diverifikasi.');
    }

    public function verifyEvent(Request $request, Competition $competition, Event $event, VerifyHeatResults $verifier): RedirectResponse
    {
        $this->authorize('seed', $competition);
        abort_unless($event->competition_id === $competition->id, 404);

        $event->load('heats');
        $count = $verifier->handleEvent($event, $request->user());

        return back()->with('status', $count.' hasil diverifikasi untuk acara '.$event->event_number.'.');
    }
}
