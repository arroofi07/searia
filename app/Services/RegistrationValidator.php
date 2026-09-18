<?php

namespace App\Services;

use App\Enums\CompetitionStatus;
use App\Enums\RegistrationStatus;
use App\Exceptions\CannotOverrideAgeGroupException;
use App\Exceptions\InvalidSwimTimeException;
use App\Models\Athlete;
use App\Models\Registration;
use App\Rules\ReasonableSwimTime;
use App\Support\SwimTime;

class RegistrationValidator
{
    public function __construct(
        private readonly AgeGroupResolver $ageGroups,
        private readonly ?AgeGroupOverride $overrides = null,
    ) {}

    /**
     * @param  list<RegistrationDraft>  $batch
     * @return list<array{code: string, message: string}>
     */
    public function validate(RegistrationDraft $draft, array $batch = []): array
    {
        $errors = [];
        $competition = $draft->competition;
        $athlete = $draft->athlete->loadMissing('club');
        $event = $draft->event->loadMissing('ageGroups');
        $batch = $batch === [] ? [$draft] : $batch;

        if ($competition->status !== CompetitionStatus::Registration) {
            $errors[] = $this->error('V-01', 'Pendaftaran sudah ditutup');
        }

        $ageGroup = $this->ageGroups->resolve($competition, $athlete->birth_year);

        if ($ageGroup === null) {
            $errors[] = $this->error('V-02', 'Usia atlet di luar rentang kejuaraan ini');
        }

        if ($draft->ageGroupOverride !== null && $ageGroup !== null) {
            try {
                $competing = $this->overrides()->decide(
                    $competition,
                    $athlete,
                    $event,
                    $draft->ageGroupOverride,
                );

                if (! $competing->is($ageGroup) && trim((string) $draft->overrideReason) === '') {
                    $errors[] = $this->error('V-10', 'Alasan naik kelas wajib diisi.');
                }

                $ageGroup = $competing;
            } catch (CannotOverrideAgeGroupException $exception) {
                $errors[] = $this->error($exception->validationCode, $exception->getMessage());
            }
        }

        if ($ageGroup !== null && ! $event->ageGroups->contains('id', $ageGroup->id)) {
            $errors[] = $this->error(
                'V-03',
                $ageGroup->name.' tidak mengikuti nomor '.$event->shortName(),
            );
        }

        if ($athlete->gender->eventGender() !== $event->gender) {
            $errors[] = $this->error(
                'V-04',
                $event->gender === \App\Enums\EventGender::Female
                    ? 'Nomor ini khusus putri'
                    : 'Nomor ini khusus putra',
            );
        }

        $alreadyStored = Registration::query()
            ->where('event_id', $event->id)
            ->where('athlete_id', $athlete->id)
            ->where('status', '!=', RegistrationStatus::Withdrawn)
            ->when(
                $draft->exceptRegistrationId !== null,
                fn ($query) => $query->whereKeyNot($draft->exceptRegistrationId),
            )
            ->exists();

        $duplicateInBatch = collect($batch)
            ->filter(fn (RegistrationDraft $item): bool => $item->athlete->is($athlete) && $item->event->is($event))
            ->count() > 1;

        if ($alreadyStored || $duplicateInBatch) {
            $errors[] = $this->error('V-05', 'Atlet sudah terdaftar di nomor ini');
        }

        $quotaUsed = $this->quotaUsed($competition->id, $athlete, $batch);

        if ($quotaUsed > $competition->max_events_per_athlete) {
            $errors[] = $this->error(
                'V-06',
                'Maksimal '.$competition->max_events_per_athlete.' nomor per atlet',
            );
        }

        $parsedMs = null;

        try {
            $parsedMs = SwimTime::parse($draft->seedTimeInput)?->milliseconds;
        } catch (InvalidSwimTimeException) {
            $errors[] = $this->error('V-07', 'Format waktu tidak valid, contoh yang benar 00:52.20');
        }

        if ($parsedMs !== null) {
            $rule = new ReasonableSwimTime($event->distance);

            if ($rule->isOutOfRange($parsedMs)) {
                $errors[] = $this->error('V-08', 'Waktu terlalu cepat untuk jarak '.$event->distance.' m');
            }
        }

        return $errors;
    }

    /**
     * @param  list<RegistrationDraft>  $drafts
     * @return array<int, list<array{code: string, message: string}>>
     */
    public function validateMany(array $drafts): array
    {
        $results = [];

        foreach ($drafts as $index => $draft) {
            $results[$index] = $this->validate($draft, $drafts);
        }

        return $results;
    }

    /**
     * @param  list<RegistrationDraft>  $batch
     */
    private function quotaUsed(int $competitionId, Athlete $athlete, array $batch): int
    {
        $exceptIds = collect($batch)
            ->map(fn (RegistrationDraft $draft): ?int => $draft->exceptRegistrationId)
            ->filter()
            ->all();

        $stored = Registration::query()
            ->where('competition_id', $competitionId)
            ->where('athlete_id', $athlete->id)
            ->get()
            ->filter(fn (Registration $registration): bool => $registration->status->countsTowardQuota())
            ->reject(fn (Registration $registration): bool => in_array($registration->id, $exceptIds, true))
            ->count();

        $incoming = collect($batch)
            ->filter(fn (RegistrationDraft $draft): bool => $draft->athlete->is($athlete))
            ->unique(fn (RegistrationDraft $draft): string => $draft->athlete->id.':'.$draft->event->id)
            ->count();

        return $stored + $incoming;
    }

    private function overrides(): AgeGroupOverride
    {
        return $this->overrides ?? new AgeGroupOverride($this->ageGroups);
    }

    /**
     * @return array{code: string, message: string}
     */
    private function error(string $code, string $message): array
    {
        return [
            'code' => $code,
            'message' => $message,
        ];
    }
}
