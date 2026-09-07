<?php

namespace App\Http\Controllers\Admin;

use App\Actions\PublishResults;
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
        PublishResults $publishResults,
    ): RedirectResponse {
        $to = CompetitionStatus::from($request->validated('status'));

        try {
            if ($to === CompetitionStatus::Published) {
                $publishResults->handle($competition, $request->user(), $request->ip());
            } else {
                $transition->transition(
                    $competition,
                    $to,
                    $request->user(),
                    $request->validated('reason'),
                    $request->ip(),
                );
            }
        } catch (CannotTransitionCompetitionException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'Status kejuaraan diubah menjadi '.$to->label().'.');
    }
}
