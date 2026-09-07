<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SitePage;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends Controller
{
    public function about(): View
    {
        return $this->show('about');
    }

    public function terms(): View
    {
        return $this->show('terms');
    }

    private function show(string $slug): View
    {
        $page = SitePage::findBySlug($slug);
        if ($page === null) {
            throw new NotFoundHttpException;
        }

        return view('public.page', ['page' => $page]);
    }
}
