<?php

use App\Enums\ClubStatus;
use App\Enums\CompetitionStatus;
use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\Gender;
use App\Enums\RegistrationStatus;
use App\Enums\Stroke;
use App\Models\AgeGroup;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/**
 * @return array{
 *     club: Club,
 *     panitia: User,
 *     competition: Competition,
 *     group: AgeGroup,
 *     event: Event,
 *     athlete: Athlete
 * }
 */
function openRegistrationMeet(): array
{
    $club = Club::factory()->create(['status' => ClubStatus::Verified]);
    $panitia = User::factory()->panitia()->create();
    $competition = Competition::factory()->status(CompetitionStatus::Registration)->create([
        'max_events_per_athlete' => 3,
        'fee_per_event' => 50_000,
        'pool_length' => 25,
    ]);
    $group = AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '3',
        'name' => 'Group 3',
        'birth_year_start' => 2015,
        'birth_year_end' => 2016,
        'sort_order' => 3,
    ]);
    $event = Event::factory()->create([
        'competition_id' => $competition->id,
        'event_number' => 13,
        'gender' => EventGender::Male,
        'distance' => 50,
        'stroke' => Stroke::Breaststroke,
        'equipment' => Equipment::None,
    ]);
    $event->ageGroups()->attach($group->id);
    $athlete = Athlete::factory()->create([
        'club_id' => $club->id,
        'gender' => Gender::Male,
        'birth_year' => 2016,
        'full_name' => 'AHZA DANISH RAHMAN',
    ]);

    return compact('club', 'panitia', 'competition', 'group', 'event', 'athlete');
}

/**
 * @param  array{competition: Competition, event: Event, athlete: Athlete, group: AgeGroup, panitia: User}  $meet
 * @param  array<string, mixed>  $overrides
 */
function verifiedRegistration(array $meet, array $overrides = []): Registration
{
    return Registration::factory()->create(array_merge([
        'competition_id' => $meet['competition']->id,
        'event_id' => $meet['event']->id,
        'athlete_id' => $meet['athlete']->id,
        'age_group_id' => $meet['group']->id,
        'registered_by' => $meet['panitia']->id,
        'status' => RegistrationStatus::Verified,
    ], $overrides));
}

/**
 * @return array{0: Competition, 1: Event, 2: AgeGroup, 3: list<Registration>}
 */
function seedMeetWithEntrants(int $count, int $lanes = 6, ?\App\Enums\SeedingMode $mode = null, int $timedCount = -1): array
{
    $meet = openRegistrationMeet();
    $meet['competition']->update([
        'pool_lanes' => $lanes,
        'seeding_mode' => $mode ?? \App\Enums\SeedingMode::Balanced,
    ]);

    if ($timedCount < 0) {
        $timedCount = $count;
    }

    $registrations = [];
    for ($i = 0; $i < $count; $i++) {
        $athlete = Athlete::factory()->create([
            'club_id' => $meet['club']->id,
            'gender' => $meet['athlete']->gender,
            'birth_year' => 2016,
            'full_name' => sprintf('ATHLETE %02d', $i + 1),
        ]);
        $registrations[] = verifiedRegistration($meet, [
            'athlete_id' => $athlete->id,
            'seed_time_ms' => $i < $timedCount ? 30_000 + ($i * 100) : null,
        ]);
    }

    return [$meet['competition']->fresh(), $meet['event'], $meet['group'], $registrations];
}

/**
 * @param  list<list<string>>  $rows
 * @param  list<string>|null  $headers
 */
function writeParticipantCsv(array $rows, ?array $headers = null): string
{
    $headers ??= [
        'NO',
        'NAMA LENGKAP',
        'L/P',
        'TAHUN LAHIR',
        'KLUB/SEKOLAH',
        'KABUPATEN/KOTA',
        'KODE ACARA',
        'CATATAN WAKTU',
    ];
    $path = tempnam(sys_get_temp_dir(), 'imp').'.csv';
    $handle = fopen($path, 'w');
    fputcsv($handle, $headers);
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    return $path;
}
