<?php

use App\Actions\CommitImportBatch;
use App\Enums\EventGender;
use App\Enums\ImportStatus;
use App\Enums\RegistrationStatus;
use App\Jobs\ValidateImportBatch;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Event;
use App\Models\ImportBatch;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use PhpOffice\PhpSpreadsheet\IOFactory;

function uploadCsv(array $meet, string $path, ?User $user = null)
{
    return test()->actingAs($user ?? User::factory()->panitia()->create())
        ->post(route('admin.imports.store', $meet['competition']), [
            'file' => new UploadedFile($path, 'peserta.csv', 'text/csv', null, true),
        ]);
}

it('downloads a template with the three required sheet names', function () {
    $meet = openRegistrationMeet();
    $panitia = User::factory()->panitia()->create();

    $response = $this->actingAs($panitia)->get(route('admin.imports.template', $meet['competition']));
    $response->assertSuccessful();

    $path = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
    $base = $response->baseResponse;
    if (method_exists($base, 'getFile')) {
        copy($base->getFile()->getPathname(), $path);
    } else {
        file_put_contents($path, $response->getContent());
    }

    $spreadsheet = IOFactory::load($path);

    expect($spreadsheet->getSheetNames())->toBe(['PESERTA', 'NOMOR LOMBA', 'PETUNJUK']);
});

it('shows all four invalid rows from an uploaded file', function () {
    $meet = openRegistrationMeet();
    Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 5,
        'gender' => EventGender::Male,
        'distance' => 50,
        'stroke' => \App\Enums\Stroke::Butterfly,
        'equipment' => \App\Enums\Equipment::Fins,
    ]);
    $second = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 15,
        'gender' => EventGender::Male,
    ]);
    $second->ageGroups()->attach($meet['group']->id);

    $path = writeParticipantCsv([
        ['1', 'AHZA VALID', 'L', '2016', $meet['club']->name, $meet['club']->city, '13', '00:52.20'],
        ['2', 'AHZA VALID', 'L', '2016', $meet['club']->name, $meet['club']->city, '15', '00:48.15'],
        ['3', 'AHZA VALID', 'L', '2016', $meet['club']->name, $meet['club']->city, '13', '00:52.20'],
        ['4', 'RANIA PUTRI', 'P', '2005', $meet['club']->name, $meet['club']->city, '13', '01:02.00'],
        ['5', 'BAGAS ADYATAMA', 'L', '2016', $meet['club']->name, $meet['club']->city, '5', '-'],
        ['6', 'NAYYARA KEI', 'L', '2016', $meet['club']->name, $meet['club']->city, '13', '52-20'],
    ]);

    $response = uploadCsv($meet, $path);
    $batch = ImportBatch::query()->latest('id')->first();

    $response->assertRedirect(route('admin.imports.show', $batch));

    $this->actingAs(User::factory()->panitia()->create())
        ->get(route('admin.imports.show', $batch))
        ->assertOk()
        ->assertSee('E-10')
        ->assertSee('E-04')
        ->assertSee('E-07')
        ->assertSee('E-08');

    expect($batch->fresh()->invalid_rows)->toBe(4);
});

it('turns an invalid row valid after it is edited on the preview screen', function () {
    $meet = openRegistrationMeet();
    $path = writeParticipantCsv([
        ['1', 'RANIA EDIT', 'L', '2005', $meet['club']->name, $meet['club']->city, '13', '00:52.20'],
    ]);
    $panitia = User::factory()->panitia()->create();
    uploadCsv($meet, $path, $panitia);
    $batch = ImportBatch::query()->latest('id')->first();

    expect($batch->invalid_rows)->toBe(1);

    $this->actingAs($panitia)
        ->patch(route('admin.imports.rows.update', [$batch, 2]), [
            'full_name' => 'RANIA EDIT',
            'gender' => 'L',
            'birth_year' => '2016',
            'club_name' => $meet['club']->name,
            'city' => $meet['club']->city,
            'event_code' => '13',
            'seed_time' => '00:52.20',
        ])
        ->assertRedirect();

    expect($batch->fresh()->invalid_rows)->toBe(0)
        ->and($batch->fresh()->valid_rows)->toBe(1);
});

it('commits valid rows into clubs, athletes, and registrations', function () {
    $meet = openRegistrationMeet();
    $second = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 15,
        'gender' => EventGender::Male,
    ]);
    $second->ageGroups()->attach($meet['group']->id);
    $path = writeParticipantCsv([
        ['1', 'IMPORT SATU', 'L', '2016', 'Klub Import Baru', 'Padang', '13', '00:52.20'],
        ['2', 'IMPORT SATU', 'L', '2016', 'Klub Import Baru', 'Padang', '15', ''],
    ]);
    $panitia = User::factory()->panitia()->create();
    uploadCsv($meet, $path, $panitia);
    $batch = ImportBatch::query()->latest('id')->first();

    $this->actingAs($panitia)
        ->post(route('admin.imports.commit', $batch))
        ->assertRedirect();

    expect(Club::query()->where('name', 'Klub Import Baru')->count())->toBe(1)
        ->and(Athlete::query()->where('full_name', 'IMPORT SATU')->count())->toBe(1)
        ->and(Registration::query()->where('import_batch_id', $batch->id)->count())->toBe(2)
        ->and($batch->fresh()->status)->toBe(ImportStatus::Committed);
});

it('rolls back the database when commit fails on the last row', function () {
    $meet = openRegistrationMeet();
    $path = writeParticipantCsv([
        ['1', 'ROLLBACK SATU', 'L', '2016', 'Klub Rollback', 'Padang', '13', '00:52.20'],
        ['2', 'ROLLBACK DUA', 'L', '2016', 'Klub Rollback', 'Padang', '13', '00:48.15'],
    ]);
    $panitia = User::factory()->panitia()->create();
    uploadCsv($meet, $path, $panitia);
    $batch = ImportBatch::query()->latest('id')->first();

    expect(fn () => app(CommitImportBatch::class)->handle($batch, function (int $index, int $total): void {
        if ($index === $total) {
            throw new RuntimeException('Gagal di baris terakhir');
        }
    }))->toThrow(RuntimeException::class);

    expect(Registration::query()->count())->toBe(0)
        ->and(Club::query()->where('name', 'Klub Rollback')->exists())->toBeFalse()
        ->and(Athlete::query()->where('full_name', 'ROLLBACK SATU')->exists())->toBeFalse();
});

it('rejects cancelling a batch after an entry has been verified', function () {
    $meet = openRegistrationMeet();
    $path = writeParticipantCsv([
        ['1', 'BATAL SATU', 'L', '2016', $meet['club']->name, $meet['club']->city, '13', '00:52.20'],
    ]);
    $panitia = User::factory()->panitia()->create();
    uploadCsv($meet, $path, $panitia);
    $batch = ImportBatch::query()->latest('id')->first();
    $this->actingAs($panitia)->post(route('admin.imports.commit', $batch));
    $batch->refresh();
    $batch->registrations()->update(['status' => RegistrationStatus::Verified]);

    $this->actingAs($panitia)
        ->from(route('admin.imports.show', $batch))
        ->delete(route('admin.imports.destroy', $batch))
        ->assertRedirect(route('admin.imports.show', $batch))
        ->assertSessionHasErrors('delete');
});

it('queues validation for files with more than two hundred rows', function () {
    Queue::fake();
    $meet = openRegistrationMeet();
    $rows = [];
    for ($i = 1; $i <= 201; $i++) {
        $rows[] = [(string) $i, 'QUEUE '.$i, 'L', '2016', $meet['club']->name, $meet['club']->city, '13', '00:52.20'];
    }
    $path = writeParticipantCsv($rows);

    uploadCsv($meet, $path);

    Queue::assertPushed(ValidateImportBatch::class);
    expect(ImportBatch::query()->latest('id')->first()->status)->toBe(ImportStatus::Validating);
});

it('validates a small file immediately without a queue job', function () {
    Queue::fake();
    $meet = openRegistrationMeet();
    $path = writeParticipantCsv([
        ['1', 'KECIL SATU', 'L', '2016', $meet['club']->name, $meet['club']->city, '13', '00:52.20'],
    ]);

    uploadCsv($meet, $path);

    Queue::assertNothingPushed();
    expect(ImportBatch::query()->latest('id')->first()->status)->toBe(ImportStatus::Validated);
});
