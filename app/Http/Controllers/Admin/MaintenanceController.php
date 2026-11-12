<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class MaintenanceController extends Controller
{
    public function clearCache(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->managesMasterData() ?? false, 403);

        Artisan::call('optimize:clear');

        return back()->with('status', 'Cache aplikasi dibersihkan (config, route, view, cache data).');
    }
}
