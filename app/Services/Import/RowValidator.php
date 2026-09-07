<?php

namespace App\Services\Import;

use App\DataTransferObjects\ParticipantRow;
use App\Enums\ClubStatus;
use App\Enums\Gender;
use App\Enums\RegistrationStatus;
use App\Exceptions\InvalidSwimTimeException;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Rules\ReasonableSwimTime;
use App\Services\AgeGroupResolver;
use App\Services\AthleteMatcher;
use App\Services\RegistrationDraft;
use App\Services\RegistrationValidator;
use App\Support\SwimTime;
use Illuminate\Support\Collection;

class RowValidator
{
    public function __construct(
        private readonly AgeGroupResolver $ageGroups,
        private readonly AthleteMatcher $matcher,
        private readonly RegistrationValidator $registrations,
    ) {}

    /**
     * @param  list<ParticipantRow>  $rows
     */
    public function validateMany(Competition $competition, array $rows, ?User $actor = null): ImportValidationResult
    {
        $context = $this->context($competition);
        $firstSeen = [];
        $rowsByAthlete = [];

        foreach ($rows as $row) {
            $key = $row->eventAthleteKey();
            $firstSeen[$key] ??= $row->excelRow;
            $rowsByAthlete[$row->athleteKey()][] = $row;
        }

        $results = [];

        foreach ($rows as $row) {
            $results[] = $this->validate($competition, $row, $rows, $context, $firstSeen, $rowsByAthlete, $actor);
        }

        return ImportValidationResult::fromRows($results);
    }

    /**
     * @param  list<ParticipantRow>  $all
     * @param  array<string, int>  $firstSeen
     * @param  array<string, list<ParticipantRow>>  $rowsByAthlete
     */
    public function validate(
        Competition $competition,
        ParticipantRow $row,
        array $all,
        array $context,
        array $firstSeen,
        array $rowsByAthlete = [],
        ?User $actor = null,
    ): ValidatedImportRow {
        $errors = [];
        $warnings = [];

        foreach ([
            ImportHeaders::NAMA_LENGKAP => $row->fullName,
            ImportHeaders::GENDER => $row->gender,
            ImportHeaders::TAHUN_LAHIR => $row->birthYear,
            ImportHeaders::KLUB => $row->clubName,
            ImportHeaders::KODE_ACARA => $row->eventCode,
        ] as $column => $value) {
            if ($value === '') {
                $errors[] = $this->issue('E-01', 'Kolom '.$column.' tidak boleh kosong');
            }
        }

        $gender = Gender::tryFrom(mb_strtoupper($row->gender));

        if ($row->gender !== '' && $gender === null) {
            $errors[] = $this->issue('E-02', 'Jenis kelamin harus L atau P');
        }

        $year = $this->parseYear($row->birthYear);
        $competitionYear = $competition->year();

        if ($row->birthYear !== '' && ($year === null || $year < 1950 || $year > $competitionYear)) {
            $errors[] = $this->issue('E-03', 'Tahun lahir tidak valid');
            $year = null;
        }

        $event = $this->event($context, $row->eventCode);

        if ($row->eventCode !== '' && $event === null) {
            $errors[] = $this->issue('E-05', 'Kode acara '.$row->eventCode.' tidak dikenal');
        }

        $club = $this->resolveClub($row, $context);
        $athlete = $this->resolveAthlete($row, $club, $year, $context);

        if ($year !== null && $this->ageGroups->resolve($competition, $year) === null) {
            $errors[] = $this->issue('E-04', 'Tahun lahir '.$year.' di luar rentang usia kejuaraan ini');
        }

        $duplicateKey = $row->eventAthleteKey();
        $firstRow = $firstSeen[$duplicateKey] ?? $row->excelRow;

        if ($firstRow !== $row->excelRow) {
            $errors[] = $this->issue('E-10', 'Baris '.$row->excelRow.' mengulang baris '.$firstRow);
        }

        if ($athlete !== null && $event !== null) {
            $alreadyStored = Registration::query()
                ->where('event_id', $event->id)
                ->where('athlete_id', $athlete->id)
                ->where('status', '!=', RegistrationStatus::Withdrawn)
                ->exists();

            if ($alreadyStored) {
                $errors[] = $this->issue('E-11', 'Atlet sudah terdaftar di kode acara '.$event->event_number);
            }
        }

        if ($event !== null && $gender !== null && $year !== null && $row->fullName !== '' && $row->clubName !== '') {
            $draftAthlete = $athlete ?? $this->makeTransientAthlete($row, $gender, $year, $club);
            $mapped = $this->registrations->validate(
                new RegistrationDraft($competition, $draftAthlete, $event, $row->seedTime === '' ? null : $row->seedTime),
                [],
                $actor,
            );

            foreach ($mapped as $item) {
                $errors[] = $this->mapValidatorIssue($item, $row, $event, $all, $competition);
            }

            $incoming = count($rowsByAthlete[$row->athleteKey()] ?? [$row]);
            $stored = 0;
            if ($athlete !== null) {
                $stored = Registration::query()
                    ->where('competition_id', $competition->id)
                    ->where('athlete_id', $athlete->id)
                    ->get()
                    ->filter(fn (Registration $registration): bool => $registration->status->countsTowardQuota())
                    ->count();
            }

            if (($stored + $incoming) > $competition->max_events_per_athlete) {
                $errors[] = $this->issue(
                    'E-12',
                    'Atlet ini memiliki '.$incoming.' baris, batasnya '.$competition->max_events_per_athlete,
                );
            }
        } elseif ($row->seedTime !== '') {
            $this->appendTimeErrors($errors, $row, $event);
        }

        $errors = $this->uniqueIssues($errors);

        if ($club === null && $row->clubName !== '') {
            $warnings[] = $this->issue('W-01', 'Klub akan dibuat baru, panitia dapat memetakannya ke klub yang sudah ada');
        }

        $clubSuggestions = $this->clubSuggestions($row, $context, $club);
        if ($clubSuggestions !== []) {
            $warnings[] = $this->issue('W-02', 'Nama klub mirip dengan klub yang sudah ada');
        }

        $athleteSuggestions = $this->athleteSuggestions($row, $club, $year, $athlete, $context);
        if ($athleteSuggestions !== []) {
            $warnings[] = $this->issue('W-03', 'Nama atlet mirip dengan atlet lain di klub yang sama');
        }

        if ($row->seedTime === '') {
            $warnings[] = $this->issue('W-04', 'Akan dicatat sebagai NT');
        }

        if ($row->city === '') {
            $warnings[] = $this->issue('W-05', 'Diisi dari data klub');
        }

        return new ValidatedImportRow(
            row: $row,
            errors: array_values(array_filter($errors)),
            warnings: $warnings,
            clubSuggestions: $clubSuggestions,
            athleteSuggestions: $athleteSuggestions,
            resolvedClubId: $row->mappedClubId ?? $club?->id,
            resolvedAthleteId: $row->mappedAthleteId ?? $athlete?->id,
        );
    }

    /**
     * @return array{
     *     events: Collection<int, Event>,
     *     clubs: Collection<int, Club>,
     *     athletes: Collection<int, Athlete>
     * }
     */
    public function context(Competition $competition): array
    {
        $events = $competition->events()->with('ageGroups')->get()->keyBy(
            fn (Event $event): int => $event->event_number,
        );

        return [
            'events' => $events,
            'clubs' => Club::query()->orderBy('name')->get(),
            'athletes' => Athlete::query()->orderBy('full_name')->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function event(array $context, string $code): ?Event
    {
        if ($code === '' || ! preg_match('/^\d+$/', $code)) {
            return null;
        }

        /** @var Collection<int, Event> $events */
        $events = $context['events'];

        return $events->get((int) $code);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveClub(ParticipantRow $row, array $context): ?Club
    {
        if ($row->mappedClubId) {
            return $context['clubs']->firstWhere('id', $row->mappedClubId);
        }

        $needle = mb_strtoupper($row->clubName);

        return $context['clubs']->first(
            fn (Club $club): bool => mb_strtoupper($club->name) === $needle,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveAthlete(ParticipantRow $row, ?Club $club, ?int $year, array $context): ?Athlete
    {
        if ($row->mappedAthleteId) {
            return $context['athletes']->firstWhere('id', $row->mappedAthleteId);
        }

        if ($club === null || $year === null || $row->fullName === '') {
            return null;
        }

        $needle = mb_strtoupper($row->fullName);

        return $context['athletes']->first(
            fn (Athlete $athlete): bool => $athlete->club_id === $club->id
                && $athlete->birth_year === $year
                && mb_strtoupper($athlete->full_name) === $needle,
        );
    }

    private function makeTransientAthlete(ParticipantRow $row, Gender $gender, int $year, ?Club $club): Athlete
    {
        $athlete = new Athlete([
            'full_name' => mb_strtoupper($row->fullName),
            'gender' => $gender,
            'birth_year' => $year,
            'club_id' => $club?->id,
        ]);

        $athlete->setRelation(
            'club',
            $club ?? new Club(['name' => $row->clubName, 'city' => $row->city, 'status' => ClubStatus::Verified]),
        );

        return $athlete;
    }

    private function parseYear(string $value): ?int
    {
        if ($value === '' || ! preg_match('/^\d{4}$/', $value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param  array{code: string, message: string}  $item
     * @param  list<ParticipantRow>  $all
     * @return array{code: string, message: string}|null
     */
    private function mapValidatorIssue(array $item, ParticipantRow $row, Event $event, array $all, Competition $competition): ?array
    {
        return match ($item['code']) {
            'V-01' => $this->issue('E-13', 'Pendaftaran sudah ditutup'),
            'V-02' => $this->issue('E-04', 'Tahun lahir '.$row->birthYear.' di luar rentang usia kejuaraan ini'),
            'V-03' => $this->issue(
                'E-07',
                ($this->ageGroups->resolve($competition, (int) $row->birthYear)?->name ?? 'Grup').' tidak mengikuti kode acara '.$event->event_number,
            ),
            'V-04' => $this->issue(
                'E-06',
                'Kode acara '.$event->event_number.' khusus '.mb_strtolower($event->gender->label()).', atlet ini '.($row->gender === 'P' ? 'putri' : 'putra'),
            ),
            'V-05' => null,
            'V-06' => null,
            'V-07' => $this->issue('E-08', 'Format waktu `'.$row->seedTime.'` tidak dikenali'),
            'V-08' => $this->issue(
                'E-09',
                'Waktu '.$row->seedTime.' terlalu cepat untuk '.$event->distance.' m',
            ),
            'V-09' => null,
            default => $this->issue($item['code'], $item['message']),
        };
    }

    /**
     * @param  list<array{code: string, message: string}|null>  $errors
     */
    private function appendTimeErrors(array &$errors, ParticipantRow $row, ?Event $event): void
    {
        try {
            $parsed = SwimTime::parse($row->seedTime);
        } catch (InvalidSwimTimeException) {
            $errors[] = $this->issue('E-08', 'Format waktu `'.$row->seedTime.'` tidak dikenali');

            return;
        }

        if ($parsed?->milliseconds !== null && $event !== null) {
            $rule = new ReasonableSwimTime($event->distance);

            if ($rule->isOutOfRange($parsed->milliseconds)) {
                $errors[] = $this->issue('E-09', 'Waktu '.$row->seedTime.' terlalu cepat untuk '.$event->distance.' m');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<array{id: int, name: string}>
     */
    private function clubSuggestions(ParticipantRow $row, array $context, ?Club $exact): array
    {
        if ($row->clubName === '') {
            return [];
        }

        return $context['clubs']
            ->filter(function (Club $club) use ($row, $exact): bool {
                if ($exact !== null && $club->id === $exact->id) {
                    return false;
                }

                return $this->matcher->areSimilar($row->clubName, $club->name);
            })
            ->take(5)
            ->map(fn (Club $club): array => ['id' => $club->id, 'name' => $club->name])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<array{id: int, full_name: string}>
     */
    private function athleteSuggestions(ParticipantRow $row, ?Club $club, ?int $year, ?Athlete $exact, array $context): array
    {
        if ($club === null || $year === null || $row->fullName === '') {
            return [];
        }

        return $context['athletes']
            ->filter(function (Athlete $athlete) use ($club, $year, $row, $exact): bool {
                if ($exact !== null && $athlete->id === $exact->id) {
                    return false;
                }

                return $athlete->club_id === $club->id
                    && $athlete->birth_year === $year
                    && $this->matcher->areSimilar($row->fullName, $athlete->full_name);
            })
            ->take(5)
            ->map(fn (Athlete $athlete): array => ['id' => $athlete->id, 'full_name' => $athlete->full_name])
            ->values()
            ->all();
    }

    /**
     * @return array{code: string, message: string}
     */
    private function issue(string $code, string $message): array
    {
        return ['code' => $code, 'message' => $message];
    }

    /**
     * @param  list<array{code: string, message: string}|null>  $issues
     * @return list<array{code: string, message: string}>
     */
    private function uniqueIssues(array $issues): array
    {
        $unique = [];

        foreach ($issues as $issue) {
            if ($issue === null) {
                continue;
            }

            $unique[$issue['code'].'|'.$issue['message']] = $issue;
        }

        return array_values($unique);
    }
}
