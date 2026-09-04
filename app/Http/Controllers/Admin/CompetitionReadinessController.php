<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Services\CompetitionReadinessCheck;
use Illuminate\View\View;

class CompetitionReadinessController extends Controller
{
    public function __invoke(Competition $competition, CompetitionReadinessCheck $readiness): View
    {
        $this->authorize('view', $competition);

        return view('admin.competitions.readiness', [
            'competition' => $competition,
            'findings' => $readiness->findings($competition),
            'ready' => $readiness->isReady($competition),
            'canOpenRegistration' => $competition->status === CompetitionStatus::Draft && $readiness->isReady($competition),
        ]);
    }
}
