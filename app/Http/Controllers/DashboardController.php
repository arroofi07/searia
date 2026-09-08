<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = request()->user();

        if ($user?->can('viewAny', Competition::class)) {
            return redirect()->route('admin.competitions.index');
        }

        if ($user?->isJuri()) {
            return redirect()->route('judge.tasks');
        }

        return redirect()->route('login');
    }
}
