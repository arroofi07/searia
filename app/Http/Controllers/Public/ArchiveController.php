<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    public function index(Request $request): View
    {
        $year = $request->filled('year') ? $request->integer('year') : null;

        $years = Competition::query()
            ->published()
            ->orderByDesc('start_date')
            ->pluck('start_date')
            ->map(fn ($date) => (int) $date->year)
            ->unique()
            ->values();

        if ($year !== null) {
            $query = Competition::query()
                ->published()
                ->whereYear('start_date', $year)
                ->orderByDesc('start_date');
            $competitions = $query->paginate(12)->withQueryString();
        } else {
            $competitions = Competition::query()
                ->published()
                ->orderByDesc('start_date')
                ->paginate(12)
                ->withQueryString();
        }

        $grouped = $competitions->getCollection()->groupBy(fn (Competition $c) => $c->start_date->year);

        return view('public.archive.index', [
            'competitions' => $competitions,
            'grouped' => $grouped,
            'years' => $years,
            'selectedYear' => $year,
        ]);
    }
}
