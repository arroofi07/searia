<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompetitionStatus;
use App\Exceptions\CannotTransitionCompetitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransitionCompetitionRequest;
use App\Models\Competition;
use App\Services\CompetitionStatusTransition;
use Illuminate\Http\RedirectResponse;

class CompetitionStatusController extends Controller
{
    public function __invoke(
        TransitionCompetitionRequest $request,
        Competition $competition,
        CompetitionStatusTransition $transition,
    ): RedirectResponse {
        $to = CompetitionStatus::from($request->validated('status'));

        try {
            $transition->transition(
                $competition,
                $to,
                $request->user(),
                $request->validated('reason'),
                $request->ip(),
            );
        } catch (CannotTransitionCompetitionException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'Status kejuaraan diubah menjadi '.$to->label().'.');
    }
}
