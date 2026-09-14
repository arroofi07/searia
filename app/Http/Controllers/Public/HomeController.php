<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Support\ListPaginator;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $open = Competition::query()
            ->openRegistration()
            ->orderBy('registration_closes_at')
            ->paginate(ListPaginator::PER_PAGE)
            ->withQueryString();

        $recent = Competition::query()
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('end_date')
            ->limit(3)
            ->get();

        return view('public.home', [
            'openCompetitions' => $open,
            'recentCompetitions' => $recent,
        ]);
    }
}
