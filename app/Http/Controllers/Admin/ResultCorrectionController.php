<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CorrectResult;
use App\Actions\UnlockHeat;
use App\Enums\DisqualificationCode;
use App\Enums\ResultStatus;
use App\Exceptions\CannotRecordResultException;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\Result;
use App\Support\SwimTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ResultCorrectionController extends Controller
{
    public function index(Competition $competition, Event $event): View
    {
        $this->authorize('seed', $competition);
        abort_unless($event->competition_id === $competition->id, 404);

        $heats = $event->heats()
            ->with([
                'ageGroup',
                'lanes' => fn ($q) => $q->orderBy('lane_number')->with([
                    'result.activityLogs.user',
                    'registration.athlete.club',
                ]),
            ])
            ->orderBy('age_group_id')
            ->orderBy('heat_number')
            ->get();

        return view('admin.results.show', [
            'competition' => $competition,
            'event' => $event,
            'heats' => $heats,
            'statuses' => ResultStatus::casesOrdered(),
            'dsqCodes' => DisqualificationCode::cases(),
            'publishedWarning' => $competition->status === \App\Enums\CompetitionStatus::Published,
            'formatTime' => fn (?int $ms): string => SwimTime::formatMilliseconds($ms),
        ]);
    }

    public function update(Request $request, Result $result, CorrectResult $correctResult): RedirectResponse
    {
        $this->authorize('correct', $result);

        $data = $request->validate([
            'status' => ['required', Rule::enum(ResultStatus::class)],
            'time' => ['nullable', 'string', 'max:32'],
            'dsq_code' => ['nullable', Rule::enum(DisqualificationCode::class)],
            'dsq_reason' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:500'],
            'confirm_published' => ['nullable', 'boolean'],
        ]);

        $competition = $result->heatLane?->heat?->event?->competition;
        if ($competition?->status === \App\Enums\CompetitionStatus::Published && empty($data['confirm_published'])) {
            return back()->withErrors([
                'confirm_published' => 'Kejuaraan sudah dipublikasikan. Centang konfirmasi untuk melanjutkan koreksi.',
            ])->withInput();
        }

        try {
            $correctResult->handle($result, $data, $request->user(), $data['reason'], $request->ip());
        } catch (CannotRecordResultException $e) {
            return back()->withErrors(['result' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'Hasil dikoreksi.');
    }

    public function unlock(Request $request, Heat $heat, UnlockHeat $unlockHeat): RedirectResponse
    {
        $this->authorize('unlock', [Result::class, $heat]);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $unlockHeat->handle($heat, $request->user(), $data['reason'], $request->ip());

        return back()->with('status', 'Kunci seri dibuka.');
    }
}
