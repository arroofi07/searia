<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SitePage;
use App\Support\PublicPageCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SitePageController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', \App\Models\Competition::class);

        return view('admin.site-pages.index', [
            'pages' => SitePage::query()->orderBy('slug')->get(),
        ]);
    }

    public function edit(SitePage $sitePage): View
    {
        $this->authorize('viewAny', \App\Models\Competition::class);

        return view('admin.site-pages.edit', ['page' => $sitePage]);
    }

    public function update(Request $request, SitePage $sitePage): RedirectResponse
    {
        $this->authorize('viewAny', \App\Models\Competition::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string'],
        ]);

        $sitePage->update($data);
        PublicPageCache::bump();

        return redirect()
            ->route('admin.site-pages.index')
            ->with('status', 'Halaman '.$sitePage->slug.' disimpan.');
    }
}
