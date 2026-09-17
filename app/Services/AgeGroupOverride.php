<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Exceptions\CannotOverrideAgeGroupException;
use App\Models\ActivityLog;
use App\Models\AgeGroup;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AgeGroupOverride
{
    public const IMPORT_STAR_REASON = 'Ditandai * pada impor Excel';

    public function __construct(private readonly AgeGroupResolver $ageGroups) {}

    public function nearestOlderEligible(Competition $competition, Athlete $athlete, Event $event): ?AgeGroup
    {
        if ($event->competition_id !== $competition->id) {
            return null;
        }

        $event->loadMissing('ageGroups');

        return $event->ageGroups
            ->filter(fn (AgeGroup $group): bool => $group->isOlderThanBirthYear($athlete->birth_year))
            ->sortByDesc(fn (AgeGroup $group): int => $group->birth_year_end)
            ->first();
    }

    public function findTarget(Competition $competition, string $label): ?AgeGroup
    {
        $label = trim($label);

        if ($label === '') {
            return null;
        }

        $stripped = preg_replace('/^group\s+/i', '', $label) ?? $label;
        $stripped = trim($stripped);

        return $this->ageGroups->groupsFor($competition)->first(
            function (AgeGroup $group) use ($label, $stripped): bool {
                return strcasecmp((string) $group->code, $stripped) === 0
                    || strcasecmp($group->name, $label) === 0
                    || strcasecmp($group->name, $stripped) === 0
                    || strcasecmp((string) $group->display_code, $stripped) === 0;
            },
        );
    }

    public function decide(Competition $competition, Athlete $athlete, Event $event, AgeGroup $target): AgeGroup
    {
        if ($event->competition_id !== $competition->id) {
            throw new CannotOverrideAgeGroupException('Nomor lomba bukan milik kejuaraan ini.');
        }

        if ($target->competition_id !== $competition->id) {
            throw new CannotOverrideAgeGroupException('Kelompok umur bukan milik kejuaraan ini.');
        }

        $natural = $this->ageGroups->resolve($competition, $athlete->birth_year);

        if ($natural === null) {
            throw new CannotOverrideAgeGroupException('Usia atlet di luar rentang kejuaraan ini.', 'V-02');
        }

        $event->loadMissing('ageGroups');

        if ($target->is($natural)) {
            $this->assertEligibleForEvent($event, $target);

            return $natural;
        }

        if (! $target->isOlderThanBirthYear($athlete->birth_year)) {
            throw new CannotOverrideAgeGroupException('Turun kelas tidak diizinkan.');
        }

        $this->assertEligibleForEvent($event, $target);

        return $target;
    }

    public function apply(
        Registration $registration,
        AgeGroup $target,
        User $actor,
        string $reason,
        ?string $ipAddress = null,
    ): Registration {
        $reason = trim($reason);

        if ($reason === '') {
            throw new CannotOverrideAgeGroupException('Alasan naik kelas wajib diisi.', 'V-10');
        }

        $registration->loadMissing(['athlete', 'event.ageGroups', 'heatLane.heat', 'competition', 'ageGroup']);

        if ($registration->status === RegistrationStatus::Withdrawn) {
            throw new CannotOverrideAgeGroupException('Entri yang dibatalkan tidak dapat dinaikkan kelas.');
        }

        $competition = $registration->competition;
        $event = $registration->event;
        $athlete = $registration->athlete;

        if ($competition === null || $event === null || $athlete === null) {
            throw new CannotOverrideAgeGroupException('Entri tidak lengkap.');
        }

        $resolved = $this->decide($competition, $athlete, $event, $target);

        if ((int) $registration->age_group_id === (int) $resolved->id) {
            return $registration;
        }

        $this->assertHeatsUnlocked($registration, $resolved);

        $from = $registration->ageGroup;

        return DB::transaction(function () use ($registration, $resolved, $from, $actor, $reason, $ipAddress): Registration {
            $lane = $registration->heatLane;

            if ($lane !== null) {
                $lane->update(['registration_id' => null]);
            }

            $registration->update(['age_group_id' => $resolved->id]);

            $updated = $registration->fresh(['ageGroup', 'athlete']);

            ActivityLog::record(
                $actor,
                'registration.age_group_override',
                $updated,
                [
                    'age_group_id' => $from?->id,
                    'age_group_name' => $from?->name,
                ],
                [
                    'age_group_id' => $updated->ageGroup?->id,
                    'age_group_name' => $updated->ageGroup?->name,
                ],
                $reason,
                $ipAddress,
            );

            return $updated;
        });
    }

    private function assertEligibleForEvent(Event $event, AgeGroup $group): void
    {
        if (! $event->ageGroups->contains('id', $group->id)) {
            throw new CannotOverrideAgeGroupException(
                $group->name.' tidak mengikuti nomor '.$event->shortName(),
                'V-03',
            );
        }
    }

    private function assertHeatsUnlocked(Registration $registration, AgeGroup $target): void
    {
        $groupIds = array_values(array_unique(array_filter([
            $registration->age_group_id,
            $target->id,
        ])));

        $locked = Heat::query()
            ->where('event_id', $registration->event_id)
            ->whereIn('age_group_id', $groupIds)
            ->whereNotNull('locked_at')
            ->exists();

        if ($locked) {
            throw new CannotOverrideAgeGroupException(
                'Tidak dapat naik kelas: seeding untuk nomor ini sudah dikunci. Buka kunci lalu seeding ulang.',
            );
        }
    }
}
