<?php

namespace App\Http\Controllers;

use App\Models\Athlete;
use App\Models\Club;
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

        if ($user?->can('viewAny', Club::class)) {
            return redirect()->route('admin.clubs.index');
        }

        if ($user?->isPelatih()) {
            return redirect()->route('registrations.index');
        }

        if ($user?->isJuri()) {
            return redirect()->route('judge.tasks');
        }

        if ($user?->club) {
            return redirect()->route('coach.club.show', $user->club);
        }

        if ($user?->can('viewAny', Athlete::class)) {
            return redirect()->route('athletes.index');
        }

        return redirect()->route('login');
    }
}
