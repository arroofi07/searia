<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CannotMergeAthletesException;
use App\Http\Controllers\Controller;
use App\Http\Requests\MergeAthleteRequest;
use App\Models\Athlete;
use App\Services\AthleteMatcher;
use App\Services\AthleteMerger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AthleteMergeController extends Controller
{
    public function create(Athlete $athlete, AthleteMatcher $matcher): View
    {
        $this->authorize('merge', $athlete);

        $athlete->load('club');

        $candidates = Athlete::query()
            ->where('club_id', $athlete->club_id)
            ->where('id', '!=', $athlete->id)
            ->orderBy('full_name')
            ->get();

        return view('admin.athletes.merge', [
            'athlete' => $athlete,
            'candidates' => $candidates,
            'similarAthletes' => $matcher->findSimilar($athlete),
        ]);
    }

    public function store(
        MergeAthleteRequest $request,
        Athlete $athlete,
        AthleteMerger $merger,
    ): RedirectResponse {
        $discard = Athlete::query()->findOrFail($request->validated('discard_athlete_id'));

        try {
            $merger->merge($athlete, $discard, $request->user(), $request->ip());
        } catch (CannotMergeAthletesException $exception) {
            return back()->withErrors([
                'discard_athlete_id' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('athletes.show', $athlete)
            ->with('status', 'Atlet berhasil digabung. Seluruh pendaftaran dipindahkan.');
    }
}
