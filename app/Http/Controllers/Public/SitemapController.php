<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $competitions = Competition::query()
            ->published()
            ->orderByDesc('published_at')
            ->get(['id', 'slug', 'published_at', 'updated_at']);

        $xml = view('public.sitemap', [
            'competitions' => $competitions,
            'staticUrls' => [
                route('home'),
                route('about'),
                route('terms'),
                route('archive.index'),
                route('public.athletes.search'),
            ],
        ])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
