<?php

namespace App\Http\Controllers\Public;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CompetitionInfoController extends Controller
{
    public function schedule(Competition $competition): View
    {
        $this->ensurePublicInfo($competition);

        $events = $competition->events()
            ->orderBy('session')
            ->orderBy('sort_order')
            ->orderBy('event_number')
            ->get()
            ->groupBy(fn ($event) => (int) $event->session);

        $now = now();

        return view('public.schedule', [
            'competition' => $competition,
            'eventsBySession' => $events,
            'milestones' => [
                [
                    'label' => 'Masa pendaftaran',
                    'start' => $competition->registration_opens_at,
                    'end' => $competition->registration_closes_at,
                    'past' => $competition->registration_closes_at->lt($now),
                ],
                [
                    'label' => 'Technical meeting',
                    'start' => $competition->technical_meeting_at,
                    'end' => $competition->technical_meeting_at,
                    'past' => $competition->technical_meeting_at !== null && $competition->technical_meeting_at->lt($now),
                ],
                [
                    'label' => 'Hari lomba',
                    'start' => $competition->start_date->startOfDay(),
                    'end' => $competition->end_date->endOfDay(),
                    'past' => $competition->end_date->endOfDay()->lt($now),
                ],
            ],
        ]);
    }

    public function fees(Competition $competition): View
    {
        $this->ensurePublicInfo($competition);

        return view('public.fees', [
            'competition' => $competition,
            'feeAvailable' => (int) $competition->fee_per_event > 0,
            'bank' => [
                'name' => (string) config('searia.invoice.bank_name'),
                'account' => (string) config('searia.invoice.bank_account'),
                'holder' => (string) config('searia.invoice.bank_holder'),
            ],
            'dueDays' => (int) config('searia.invoice.due_days', 7),
        ]);
    }

    private function ensurePublicInfo(Competition $competition): void
    {
        if ($competition->status === CompetitionStatus::Draft) {
            throw new NotFoundHttpException;
        }
    }
}
