<?php

namespace App\Services;

use App\Exceptions\CannotMergeAthletesException;
use App\Models\ActivityLog;
use App\Models\Athlete;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AthleteMerger
{
    public function merge(Athlete $keep, Athlete $discard, User $actor, ?string $ipAddress = null): Athlete
    {
        if ($keep->is($discard)) {
            throw new CannotMergeAthletesException('Tidak dapat menggabungkan atlet dengan dirinya sendiri.');
        }

        if ($keep->club_id !== $discard->club_id) {
            throw new CannotMergeAthletesException('Penggabungan hanya untuk atlet dalam klub yang sama.');
        }

        $keepEventIds = $keep->registrations()->pluck('event_id');

        $hasConflict = $discard->registrations()
            ->whereIn('event_id', $keepEventIds)
            ->exists();

        if ($hasConflict) {
            throw new CannotMergeAthletesException(
                'Penggabungan ditolak karena kedua atlet terdaftar pada nomor lomba yang sama.',
            );
        }

        return DB::transaction(function () use ($keep, $discard, $actor, $ipAddress): Athlete {
            $movedRegistrationIds = $discard->registrations()->pluck('id')->all();

            $discard->registrations()->update(['athlete_id' => $keep->id]);

            ActivityLog::query()->create([
                'user_id' => $actor->id,
                'action' => 'athlete.merge',
                'subject_type' => Athlete::class,
                'subject_id' => $keep->id,
                'old_values' => [
                    'discarded_athlete' => [
                        'id' => $discard->id,
                        'full_name' => $discard->full_name,
                        'club_id' => $discard->club_id,
                        'gender' => $discard->gender->value,
                        'birth_year' => $discard->birth_year,
                        'birth_date' => $discard->birth_date?->toDateString(),
                        'identity_number' => $discard->identity_number,
                    ],
                ],
                'new_values' => [
                    'kept_athlete_id' => $keep->id,
                    'moved_registration_ids' => $movedRegistrationIds,
                ],
                'reason' => 'Penggabungan atlet ganda',
                'ip_address' => $ipAddress,
            ]);

            $discard->delete();

            return $keep->refresh();
        });
    }
}
