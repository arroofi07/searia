<?php

use App\Actions\GenerateCertificates;
use App\Actions\LockHeat;
use App\Actions\PublishResults;
use App\Actions\RecordLaneResult;
use App\Actions\RunSeeding;
use App\Actions\VerifyHeatResults;
use App\Enums\CompetitionStatus;
use App\Enums\ResultStatus;
use App\Exports\MedalTallyExport;
use App\Exports\ParticipantExport;
use App\Exports\ResultExport;
use App\Exports\StartListExport;
use App\Imports\BlankResultSheetImport;
use App\Imports\ParticipantSheetImport;
use App\Jobs\GenerateCertificateArchive;
use App\Models\Certificate;
use App\Models\Club;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Result;
use App\Models\User;
use App\Notifications\CertificateArchiveReady;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

function publishedMeetForExport(): array
{
    [$competition, $event, $group, $registrations] = seedMeetWithEntrants(3, lanes: 6);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $heat = Heat::query()->where('event_id', $event->id)->firstOrFail();
    $judge = User::factory()->juri()->create();
    $event->judges()->attach($judge->id);
    $admin = User::factory()->panitia()->create();

    foreach (HeatLane::query()->where('heat_id', $heat->id)->whereNotNull('registration_id')->get() as $lane) {
        $seed = $lane->registration->seed_time_ms ?? 40_000;
        app(RecordLaneResult::class)->handle($lane, [
            'status' => ResultStatus::Ok,
            'time_ms' => $seed,
        ], $judge);
    }

    app(LockHeat::class)->handle($heat->fresh(), $judge);
    app(VerifyHeatResults::class)->handleHeat($heat->fresh(), $admin);
    $competition->update(['status' => CompetitionStatus::Finished]);
    app(PublishResults::class)->handle($competition->fresh(), $admin);

    return compact('competition', 'event', 'group', 'heat', 'judge', 'admin', 'registrations');
}

it('exports participants with import-compatible columns and reimports without format errors', function () {
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);

    $filename = 'testing-peserta-'.uniqid().'.xlsx';
    Excel::store(new ParticipantExport($meet['competition']), $filename, 'local');
    $stored = storage_path('app/private/'.$filename);

    $spreadsheet = IOFactory::load($stored);
    $headers = $spreadsheet->getActiveSheet()->rangeToArray('A1:J1')[0];

    expect($headers)->toBe([
        'NO',
        'NAMA LENGKAP',
        'L/P',
        'TAHUN LAHIR',
        'KLUB/SEKOLAH',
        'KABUPATEN/KOTA',
        'KODE ACARA',
        'CATATAN WAKTU',
        'STATUS',
        'ALASAN PENOLAKAN',
    ]);

    $import = new ParticipantSheetImport;
    $import->collection(collect($spreadsheet->getActiveSheet()->toArray()));

    expect($import->missingColumns)->toBe([])
        ->and($import->rows)->not->toBeEmpty();

    @unlink($stored);
});

it('exports start list, results, and medals with expected row counts', function () {
    $meet = publishedMeetForExport();
    $competition = $meet['competition']->fresh();

    expect((new StartListExport($competition))->collection())->toHaveCount(3)
        ->and((new ResultExport($competition))->collection())->toHaveCount(3)
        ->and((new MedalTallyExport($competition))->collection()->count())->toBeGreaterThan(0);
});

it('imports filled blank result sheet into results', function () {
    [$competition, $event, $group, $registrations] = seedMeetWithEntrants(2, lanes: 6);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $admin = User::factory()->panitia()->create();
    $heat = Heat::query()->where('event_id', $event->id)->firstOrFail();
    $lanes = HeatLane::query()->where('heat_id', $heat->id)->whereNotNull('registration_id')->orderBy('lane_number')->get();

    $rows = collect([
        collect([
            'kode_acara' => $event->event_number,
            'kelompok_umur' => $group->name,
            'seri' => $heat->heat_number,
            'lintasan' => $lanes[0]->lane_number,
            'hasil' => '00:45.00',
            'status' => 'OK',
            'dsq' => '',
        ]),
        collect([
            'kode_acara' => $event->event_number,
            'kelompok_umur' => $group->name,
            'seri' => $heat->heat_number,
            'lintasan' => $lanes[1]->lane_number,
            'hasil' => '',
            'status' => 'DNS',
            'dsq' => '',
        ]),
    ]);

    $import = new BlankResultSheetImport($competition, $admin);
    $import->collection($rows);

    expect($import->imported)->toBe(2)
        ->and(Result::query()->count())->toBe(2)
        ->and($lanes[0]->fresh()->result?->status)->toBe(ResultStatus::Ok)
        ->and($lanes[1]->fresh()->result?->status)->toBe(ResultStatus::Dns);
});

it('rejects certificate download when competition is only finished', function () {
    [$competition, $event, $group, $registrations] = seedMeetWithEntrants(2, lanes: 6);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $heat = Heat::query()->where('event_id', $event->id)->firstOrFail();
    $judge = User::factory()->juri()->create();
    $event->judges()->attach($judge->id);
    $admin = User::factory()->panitia()->create();

    foreach (HeatLane::query()->where('heat_id', $heat->id)->whereNotNull('registration_id')->get() as $lane) {
        app(RecordLaneResult::class)->handle($lane, [
            'status' => ResultStatus::Ok,
            'time_ms' => 40_000,
        ], $judge);
    }
    app(LockHeat::class)->handle($heat->fresh(), $judge);
    app(VerifyHeatResults::class)->handleHeat($heat->fresh(), $admin);
    $competition->update(['status' => CompetitionStatus::Finished]);

    $competition->forceFill(['status' => CompetitionStatus::Published])->save();
    app(GenerateCertificates::class)->handle($competition->fresh());
    $certificate = Certificate::query()->firstOrFail();
    $competition->update(['status' => CompetitionStatus::Finished, 'published_at' => null]);

    $this->actingAs($admin)
        ->get(route('certificates.download', $certificate))
        ->assertForbidden();
});

it('dispatches archive job and forbids coach from another club', function () {
    Queue::fake();
    $meet = publishedMeetForExport();
    $otherClub = Club::factory()->create();
    $otherCoach = User::factory()->pelatih()->create(['club_id' => $otherClub->id]);
    $ownCoach = User::factory()->pelatih()->create(['club_id' => $meet['registrations'][0]->athlete->club_id]);

    $this->actingAs($otherCoach)
        ->post(route('certificates.archive', $meet['competition']), ['club_id' => $meet['registrations'][0]->athlete->club_id])
        ->assertForbidden();

    $this->actingAs($ownCoach)
        ->post(route('certificates.archive', $meet['competition']))
        ->assertRedirect();

    Queue::assertPushed(GenerateCertificateArchive::class);
});

it('verifies a valid certificate code and shows not found for unknown codes', function () {
    $meet = publishedMeetForExport();
    $certificate = Certificate::query()->where('competition_id', $meet['competition']->id)->firstOrFail();

    $this->get(route('certificates.verify', $certificate->code))
        ->assertOk()
        ->assertSee($certificate->athlete->full_name)
        ->assertSee((string) $meet['event']->event_number);

    $this->get(route('certificates.verify', 'KODEBOGUS999'))
        ->assertOk()
        ->assertSee('tidak ditemukan');
});

it('downloads export endpoints for panitia', function () {
    $meet = publishedMeetForExport();
    $admin = $meet['admin'];

    $this->actingAs($admin)->get(route('admin.exports.index', $meet['competition']))->assertOk();
    $this->actingAs($admin)->get(route('admin.exports.participants', $meet['competition']))->assertOk();
    $this->actingAs($admin)->get(route('admin.exports.start-list', $meet['competition']))->assertOk();
    $this->actingAs($admin)->get(route('admin.exports.results', $meet['competition']))->assertOk();
    $this->actingAs($admin)->get(route('admin.exports.medals', $meet['competition']))->assertOk();
    $this->actingAs($admin)->get(route('admin.exports.blank-results', $meet['competition']))->assertOk();
});
