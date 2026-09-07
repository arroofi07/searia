<?php

namespace App\Actions;

use App\Enums\ClubStatus;
use App\Enums\ClubType;
use App\Enums\Gender;
use App\Enums\ImportStatus;
use App\Enums\RegistrationStatus;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Event;
use App\Models\ImportBatch;
use App\Models\Registration;
use App\Services\AgeGroupResolver;
use App\Services\Import\ValidatedImportRow;
use App\Support\SwimTime;
use Closure;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CommitImportBatch
{
    public function __construct(private readonly AgeGroupResolver $ageGroups) {}

    public function handle(ImportBatch $batch, ?Closure $afterEach = null): void
    {
        DB::transaction(function () use ($batch, $afterEach): void {
            $result = $batch->result();
            $valid = $result->validRows();
            $total = count($valid);
            $clubs = [];
            $athletes = [];
            $index = 0;

            foreach ($valid as $row) {
                $index++;
                $club = $this->club($row, $clubs);
                $athlete = $this->athlete($row, $club, $athletes);
                $event = Event::query()
                    ->where('competition_id', $batch->competition_id)
                    ->where('event_number', (int) $row->row->eventCode)
                    ->firstOrFail();
                $ageGroup = $this->ageGroups->resolve($batch->competition, $athlete->birth_year);

                Registration::query()->create([
                    'competition_id' => $batch->competition_id,
                    'event_id' => $event->id,
                    'athlete_id' => $athlete->id,
                    'age_group_id' => $ageGroup?->id,
                    'seed_time_ms' => SwimTime::parse($row->row->seedTime === '' ? null : $row->row->seedTime)?->milliseconds,
                    'status' => RegistrationStatus::Pending,
                    'registered_by' => $batch->user_id,
                    'import_batch_id' => $batch->id,
                ]);

                if ($afterEach !== null) {
                    $afterEach($index, $total, $row);
                }
            }

            $batch->update([
                'status' => ImportStatus::Committed,
                'committed_at' => now(),
                'valid_rows' => $total,
            ]);
        });
    }

    /**
     * @param  array<string, Club>  $cache
     */
    private function club(ValidatedImportRow $row, array &$cache): Club
    {
        $mapped = $row->row->mappedClubId ?? $row->resolvedClubId;

        if ($mapped) {
            return $cache['id:'.$mapped] ??= Club::query()->findOrFail($mapped);
        }

        $key = mb_strtoupper($row->row->clubName);

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $existing = Club::query()->get()->first(
            fn (Club $club): bool => mb_strtoupper($club->name) === $key,
        );

        if ($existing !== null) {
            return $cache[$key] = $existing;
        }

        $created = Club::query()->create([
            'name' => $row->row->clubName,
            'short_name' => mb_substr($row->row->clubName, 0, 12),
            'type' => ClubType::Perkumpulan,
            'city' => $row->row->city !== '' ? $row->row->city : '—',
            'status' => ClubStatus::Verified,
            'is_active' => true,
        ]);

        return $cache[$key] = $created;
    }

    /**
     * @param  array<string, Athlete>  $cache
     */
    private function athlete(ValidatedImportRow $row, Club $club, array &$cache): Athlete
    {
        $mapped = $row->row->mappedAthleteId ?? $row->resolvedAthleteId;

        if ($mapped) {
            return $cache['id:'.$mapped] ??= Athlete::query()->findOrFail($mapped);
        }

        $key = $club->id.'|'.mb_strtoupper($row->row->fullName).'|'.$row->row->birthYear;

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $year = (int) $row->row->birthYear;
        $name = mb_strtoupper($row->row->fullName);
        $existing = Athlete::query()
            ->where('club_id', $club->id)
            ->where('birth_year', $year)
            ->get()
            ->first(fn (Athlete $athlete): bool => mb_strtoupper($athlete->full_name) === $name);

        if ($existing !== null) {
            return $cache[$key] = $existing;
        }

        $gender = Gender::tryFrom(mb_strtoupper($row->row->gender));

        if ($gender === null) {
            throw new RuntimeException('Jenis kelamin tidak valid pada baris '.$row->row->excelRow);
        }

        $created = Athlete::query()->create([
            'club_id' => $club->id,
            'full_name' => $name,
            'gender' => $gender,
            'birth_year' => $year,
            'is_active' => true,
        ]);

        return $cache[$key] = $created;
    }
}
