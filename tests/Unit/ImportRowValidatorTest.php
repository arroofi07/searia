<?php

use App\DataTransferObjects\ParticipantRow;
use App\Enums\CompetitionStatus;
use App\Enums\EventGender;
use App\Enums\RegistrationStatus;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Event;
use App\Models\Registration;
use App\Services\Import\RowValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function importRow(array $meet, array $overrides = []): ParticipantRow
{
    return new ParticipantRow(
        excelRow: $overrides['excel_row'] ?? 2,
        no: (string) ($overrides['no'] ?? '1'),
        fullName: $overrides['full_name'] ?? 'PESERTA BARU SATU',
        gender: $overrides['gender'] ?? 'L',
        birthYear: $overrides['birth_year'] ?? '2016',
        clubName: $overrides['club_name'] ?? $meet['club']->name,
        city: $overrides['city'] ?? (string) $meet['club']->city,
        eventCode: $overrides['event_code'] ?? '13',
        seedTime: array_key_exists('seed_time', $overrides) ? (string) $overrides['seed_time'] : '00:52.20',
    );
}

function importCodes(array $meet, ParticipantRow $row, array $batch = []): array
{
    $validator = app(RowValidator::class);
    $all = $batch === [] ? [$row] : $batch;
    $result = $validator->validateMany($meet['competition'], $all, $meet['panitia']);

    return collect($result->rows)
        ->first(fn ($item) => $item->row->excelRow === $row->excelRow)
        ->errors;
}

it('reports E-01 when a required column is empty', function () {
    $meet = openRegistrationMeet();
    $codes = collect(importCodes($meet, importRow($meet, ['full_name' => ''])))->pluck('code');

    expect($codes->all())->toContain('E-01');
});

it('reports E-02 when gender is not L or P', function () {
    $meet = openRegistrationMeet();
    $errors = importCodes($meet, importRow($meet, ['gender' => 'X']));

    expect(collect($errors)->pluck('code')->all())->toContain('E-02')
        ->and(collect($errors)->firstWhere('code', 'E-02')['message'])->toBe('Jenis kelamin harus L atau P');
});

it('reports E-03 when the birth year is not four digits', function () {
    $meet = openRegistrationMeet();
    $errors = importCodes($meet, importRow($meet, ['birth_year' => '16']));

    expect(collect($errors)->pluck('code')->all())->toContain('E-03');
});

it('reports E-04 when the birth year matches no age group', function () {
    $meet = openRegistrationMeet();
    $errors = importCodes($meet, importRow($meet, ['birth_year' => '2005']));

    expect(collect($errors)->firstWhere('code', 'E-04')['message'])->toBe('Tahun lahir 2005 di luar rentang usia kejuaraan ini');
});

it('reports E-05 when the event code is unknown', function () {
    $meet = openRegistrationMeet();
    $errors = importCodes($meet, importRow($meet, ['event_code' => '99']));

    expect(collect($errors)->firstWhere('code', 'E-05')['message'])->toBe('Kode acara 99 tidak dikenal');
});

it('reports E-06 when athlete gender does not match the event', function () {
    $meet = openRegistrationMeet();
    $pi = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 14,
        'gender' => EventGender::Female,
    ]);
    $pi->ageGroups()->attach($meet['group']->id);

    $errors = importCodes($meet, importRow($meet, ['event_code' => '14']));

    expect(collect($errors)->pluck('code')->all())->toContain('E-06');
});

it('reports E-07 when the age group is not eligible for the event', function () {
    $meet = openRegistrationMeet();
    Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 5,
        'gender' => EventGender::Male,
        'distance' => 50,
        'stroke' => \App\Enums\Stroke::Butterfly,
        'equipment' => \App\Enums\Equipment::Fins,
    ]);

    $errors = importCodes($meet, importRow($meet, ['event_code' => '5']));

    expect(collect($errors)->firstWhere('code', 'E-07')['message'])->toContain('tidak mengikuti kode acara 5');
});

it('reports E-08 when the seed time cannot be parsed', function () {
    $meet = openRegistrationMeet();
    $errors = importCodes($meet, importRow($meet, ['seed_time' => '52-20']));

    expect(collect($errors)->firstWhere('code', 'E-08')['message'])->toBe('Format waktu `52-20` tidak dikenali');
});

it('reports E-09 when the seed time is unrealistically fast', function () {
    $meet = openRegistrationMeet();
    $errors = importCodes($meet, importRow($meet, ['seed_time' => '00:05.20']));

    expect(collect($errors)->firstWhere('code', 'E-09')['message'])->toBe('Waktu 00:05.20 terlalu cepat untuk 50 m');
});

it('reports E-10 when the same athlete and event appear twice in the file', function () {
    $meet = openRegistrationMeet();
    $first = importRow($meet, ['excel_row' => 7, 'full_name' => 'AHZA DUPLIKAT']);
    $second = importRow($meet, ['excel_row' => 12, 'full_name' => 'AHZA DUPLIKAT']);
    $errors = importCodes($meet, $second, [$first, $second]);

    expect(collect($errors)->firstWhere('code', 'E-10')['message'])->toBe('Baris 12 mengulang baris 7');
});

it('reports E-11 when the athlete is already registered in the event', function () {
    $meet = openRegistrationMeet();
    Registration::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_id' => $meet['event']->id,
        'athlete_id' => $meet['athlete']->id,
        'age_group_id' => $meet['group']->id,
        'registered_by' => $meet['panitia']->id,
        'status' => RegistrationStatus::Pending,
    ]);

    $errors = importCodes($meet, importRow($meet, [
        'full_name' => $meet['athlete']->full_name,
        'club_name' => $meet['club']->name,
    ]));

    expect(collect($errors)->pluck('code')->all())->toContain('E-11');
});

it('reports E-12 when the athlete exceeds the event quota', function () {
    $meet = openRegistrationMeet();
    $rows = [importRow($meet, ['excel_row' => 2, 'event_code' => '13', 'full_name' => 'KUOTA ATLET'])];
    foreach ([15, 17, 19] as $index => $number) {
        $event = Event::factory()->create([
            'competition_id' => $meet['competition']->id,
            'event_number' => $number,
            'gender' => EventGender::Male,
        ]);
        $event->ageGroups()->attach($meet['group']->id);
        $rows[] = importRow($meet, [
            'excel_row' => $index + 3,
            'event_code' => (string) $number,
            'full_name' => 'KUOTA ATLET',
        ]);
    }

    $errors = importCodes($meet, $rows[0], $rows);

    expect(collect($errors)->firstWhere('code', 'E-12')['message'])->toBe('Atlet ini memiliki 4 baris, batasnya 3');
});

it('reports E-13 when registration is closed', function () {
    $meet = openRegistrationMeet();
    $meet['competition']->update(['status' => CompetitionStatus::Closed]);
    $errors = importCodes($meet, importRow($meet));

    expect(collect($errors)->firstWhere('code', 'E-13')['message'])->toBe('Pendaftaran sudah ditutup');
});

it('reports W-01 when the club name is new', function () {
    $meet = openRegistrationMeet();
    $result = app(RowValidator::class)->validateMany($meet['competition'], [
        importRow($meet, ['club_name' => 'Klub Baru Import']),
    ], $meet['panitia']);

    expect(collect($result->rows[0]->warnings)->pluck('code')->all())->toContain('W-01');
});

it('reports W-02 when the club name is similar to an existing club', function () {
    $meet = openRegistrationMeet();
    Club::factory()->create(['name' => 'SeaRIA Aquatic Padang']);
    $result = app(RowValidator::class)->validateMany($meet['competition'], [
        importRow($meet, ['club_name' => 'SeaRIA Aquatic Pdg']),
    ], $meet['panitia']);

    expect(collect($result->rows[0]->warnings)->pluck('code')->all())->toContain('W-02')
        ->and($result->rows[0]->clubSuggestions)->not->toBeEmpty();
});

it('reports W-03 when an athlete name is similar in the same club', function () {
    $meet = openRegistrationMeet();
    Athlete::factory()->create([
        'club_id' => $meet['club']->id,
        'full_name' => 'AHZA DANISH RAHMANN',
        'birth_year' => 2016,
        'gender' => $meet['athlete']->gender,
    ]);

    $result = app(RowValidator::class)->validateMany($meet['competition'], [
        importRow($meet, ['full_name' => 'AHZA DANISH RAHMAN', 'club_name' => $meet['club']->name]),
    ], $meet['panitia']);

    expect(collect($result->rows[0]->warnings)->pluck('code')->all())->toContain('W-03');
});

it('reports W-04 when the seed time is empty', function () {
    $meet = openRegistrationMeet();
    $result = app(RowValidator::class)->validateMany($meet['competition'], [
        importRow($meet, ['seed_time' => '', 'full_name' => 'TANPA WAKTU']),
    ], $meet['panitia']);

    expect(collect($result->rows[0]->warnings)->pluck('code')->all())->toContain('W-04')
        ->and($result->rows[0]->isValid())->toBeTrue();
});

it('reports W-05 when the city is empty', function () {
    $meet = openRegistrationMeet();
    $result = app(RowValidator::class)->validateMany($meet['competition'], [
        importRow($meet, ['city' => '', 'full_name' => 'TANPA KOTA']),
    ], $meet['panitia']);

    expect(collect($result->rows[0]->warnings)->pluck('code')->all())->toContain('W-05');
});

it('validates two thousand rows in under thirty seconds', function () {
    $meet = openRegistrationMeet();
    $rows = [];
    for ($i = 1; $i <= 2000; $i++) {
        $rows[] = importRow($meet, [
            'excel_row' => $i + 1,
            'full_name' => 'ATLET KINERJA '.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
        ]);
    }

    $started = microtime(true);
    $result = app(RowValidator::class)->validateMany($meet['competition'], $rows, $meet['panitia']);
    $elapsed = microtime(true) - $started;

    expect($result->valid)->toBe(2000)
        ->and($elapsed)->toBeLessThan(30);
});
