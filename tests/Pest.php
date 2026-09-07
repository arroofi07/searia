<?php

use App\Enums\ClubStatus;
use App\Enums\CompetitionStatus;
use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\Gender;
use App\Enums\Stroke;
use App\Models\AgeGroup;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Competition;
use App\Models\Event;
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
 *     coach: User,
 *     competition: Competition,
 *     group: AgeGroup,
 *     event: Event,
 *     athlete: Athlete
 * }
 */
function openRegistrationMeet(): array
{
    $club = Club::factory()->create(['status' => ClubStatus::Verified]);
    $coach = User::factory()->pelatih($club)->create();
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

    return compact('club', 'coach', 'competition', 'group', 'event', 'athlete');
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
