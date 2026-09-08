<?php

use App\Enums\ImportStatus;
use App\Enums\RegistrationStatus;
use App\Exceptions\CannotCancelImportBatchException;
use App\Exceptions\MissingImportColumnsException;
use App\Models\ImportBatch;
use App\Models\Registration;
use App\Services\Import\ImportHeaders;
use App\Services\Import\ParticipantFileReader;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reads headers regardless of extra spaces and letter case', function () {
    $meet = openRegistrationMeet();
    $path = writeParticipantCsv([
        ['1', 'PESERTA HEADER', 'L', '2016', $meet['club']->name, $meet['club']->city, '13', '00:52.20'],
    ], [
        'no',
        'Nama  Lengkap',
        'l/p',
        'tahun lahir',
        'klub/sekolah',
        'kabupaten/kota',
        'kode acara',
        'catatan waktu',
    ]);

    $result = app(ParticipantFileReader::class)->readAndValidate($path, 'csv', $meet['competition'], $meet['panitia']);

    expect($result->read)->toBe(1)
        ->and($result->rows[0]->row->fullName)->toBe('PESERTA HEADER')
        ->and($result->rows[0]->row->excelRow)->toBe(2);
});

it('rejects a file that is missing the KODE ACARA column', function () {
    $meet = openRegistrationMeet();
    $path = writeParticipantCsv([
        ['1', 'PESERTA', 'L', '2016', $meet['club']->name, $meet['club']->city, '00:52.20'],
    ], [
        'NO',
        'NAMA LENGKAP',
        'L/P',
        'TAHUN LAHIR',
        'KLUB/SEKOLAH',
        'KABUPATEN/KOTA',
        'CATATAN WAKTU',
    ]);

    expect(fn () => app(ParticipantFileReader::class)->readAndValidate($path, 'csv', $meet['competition'], $meet['panitia']))
        ->toThrow(MissingImportColumnsException::class);

    try {
        app(ParticipantFileReader::class)->readAndValidate($path, 'csv', $meet['competition'], $meet['panitia']);
    } catch (MissingImportColumnsException $exception) {
        expect($exception->columns)->toContain(ImportHeaders::KODE_ACARA)
            ->and($exception->getMessage())->toContain('KODE ACARA');
    }
});

it('skips empty rows in the middle of the file', function () {
    $meet = openRegistrationMeet();
    $path = writeParticipantCsv([
        ['1', 'PESERTA SATU', 'L', '2016', $meet['club']->name, $meet['club']->city, '13', '00:52.20'],
        ['', '', '', '', '', '', '', ''],
        ['3', 'PESERTA DUA', 'L', '2016', $meet['club']->name, $meet['club']->city, '13', '00:48.15'],
    ]);

    $result = app(ParticipantFileReader::class)->readAndValidate($path, 'csv', $meet['competition'], $meet['panitia']);

    expect($result->read)->toBe(2)
        ->and($result->rows[1]->row->excelRow)->toBe(4);
});

it('cancels a batch by deleting every registration that came from it', function () {
    $meet = openRegistrationMeet();
    $batch = ImportBatch::factory()->create([
        'competition_id' => $meet['competition']->id,
        'user_id' => $meet['panitia']->id,
        'status' => ImportStatus::Committed,
    ]);
    Registration::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_id' => $meet['event']->id,
        'athlete_id' => $meet['athlete']->id,
        'age_group_id' => $meet['group']->id,
        'registered_by' => $meet['panitia']->id,
        'import_batch_id' => $batch->id,
        'status' => RegistrationStatus::Pending,
    ]);

    $batch->cancel();

    expect(Registration::query()->count())->toBe(0)
        ->and($batch->fresh()->status)->toBe(ImportStatus::Cancelled);
});

it('refuses to cancel a batch after an entry has been verified', function () {
    $meet = openRegistrationMeet();
    $batch = ImportBatch::factory()->create([
        'competition_id' => $meet['competition']->id,
        'user_id' => $meet['panitia']->id,
        'status' => ImportStatus::Committed,
    ]);
    Registration::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_id' => $meet['event']->id,
        'athlete_id' => $meet['athlete']->id,
        'age_group_id' => $meet['group']->id,
        'registered_by' => $meet['panitia']->id,
        'import_batch_id' => $batch->id,
        'status' => RegistrationStatus::Verified,
    ]);

    $batch->cancel();
})->throws(CannotCancelImportBatchException::class);
