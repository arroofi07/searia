<?php

namespace App\Services;

use App\Models\Competition;

class CompetitionReadinessCheck
{
    /**
     * @return list<array{code: string, message: string, blocking: bool}>
     */
    public function findings(Competition $competition): array
    {
        $competition->load(['ageGroups.events', 'events.ageGroups']);

        $findings = [];

        if ($competition->ageGroups->isEmpty()) {
            $findings[] = [
                'code' => 'no_age_groups',
                'message' => 'Kejuaraan belum memiliki kelompok umur.',
                'blocking' => true,
            ];
        }

        if ($competition->events->isEmpty()) {
            $findings[] = [
                'code' => 'no_events',
                'message' => 'Kejuaraan belum memiliki nomor lomba.',
                'blocking' => true,
            ];
        }

        foreach ($competition->events as $event) {
            if ($event->ageGroups->isEmpty()) {
                $findings[] = [
                    'code' => 'event_without_age_group',
                    'message' => 'Nomor '.$event->event_number.' ('.$event->formattedName().') tidak memiliki kelompok umur pada matriks kelayakan.',
                    'blocking' => true,
                ];
            }
        }

        foreach ($competition->ageGroups as $group) {
            if ($group->events->isEmpty()) {
                $findings[] = [
                    'code' => 'age_group_without_event',
                    'message' => $group->name.' tidak dapat mengikuti satu nomor pun.',
                    'blocking' => true,
                ];
            }
        }

        if ($competition->fee_per_event <= 0) {
            $findings[] = [
                'code' => 'zero_fee',
                'message' => 'Biaya per nomor masih bernilai nol.',
                'blocking' => true,
            ];
        }

        return $findings;
    }

    public function isReady(Competition $competition): bool
    {
        return $this->findings($competition) === [];
    }
}
