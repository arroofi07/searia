<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use Illuminate\View\View;

class ResultOverviewController extends Controller
{
    public function index(Competition $competition): View
    {
        $this->authorize('seed', $competition);

        $events = $competition->events()
            ->withCount([
                'heats',
                'heats as locked_heats_count' => fn ($q) => $q->whereNotNull('results_locked_at'),
            ])
            ->orderBy('session')
            ->orderBy('sort_order')
            ->orderBy('event_number')
            ->get();

        return view('admin.results.index', [
            'competition' => $competition,
            'events' => $events,
        ]);
    }
}
