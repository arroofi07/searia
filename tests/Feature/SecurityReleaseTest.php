<?php

use App\Actions\CorrectResult;
use App\Actions\LockHeat;
use App\Actions\PublishResults;
use App\Actions\RecordLaneResult;
use App\Actions\RunSeeding;
use App\Actions\VerifyHeatResults;
use App\Enums\CompetitionStatus;
use App\Enums\ResultStatus;
use App\Models\ActivityLog;
use App\Models\Competition;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\User;
use App\Support\UploadedFileGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('forbids judges from opening the audit screen', function () {
    $judge = User::factory()->juri()->create();

    $this->actingAs($judge)
        ->get(route('admin.activity-logs.index'))
        ->assertForbidden();
});

it('records a complete audit entry when correcting a result', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(2, lanes: 6);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $heat = Heat::query()->where('event_id', $event->id)->firstOrFail();
    $judge = User::factory()->juri()->create();
    $event->judges()->attach($judge->id);
    $admin = User::factory()->panitia()->create();
    $lane = HeatLane::query()->where('heat_id', $heat->id)->whereNotNull('registration_id')->firstOrFail();
    $result = app(RecordLaneResult::class)->handle($lane, ['status' => ResultStatus::Ok, 'time_ms' => 40_000], $judge);

    app(CorrectResult::class)->handle($result, [
        'status' => ResultStatus::Ok,
        'time_ms' => 41_000,
    ], $admin, 'Kesalahan ketik', '127.0.0.1');

    $log = ActivityLog::query()->where('action', 'result.correct')->first();
    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($admin->id)
        ->and($log->reason)->toBe('Kesalahan ketik')
        ->and($log->ip_address)->toBe('127.0.0.1')
        ->and($log->old_values)->toBeArray()
        ->and($log->new_values)->toBeArray();
});

it('returns 429 on the sixth login attempt within a minute', function () {
    RateLimiter::clear('login');

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login'), [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ]);
    }

    $this->post(route('login'), [
        'email' => 'nobody@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(429);
});

it('rejects a php payload renamed as jpg', function () {
    $path = tempnam(sys_get_temp_dir(), 'evil').'.jpg';
    file_put_contents($path, "<?php echo 'hack';");
    $file = new UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true);

    expect(fn () => UploadedFileGuard::assertSafe($file, ['image/jpeg', 'image/png'], 2_000_000))
        ->toThrow(ValidationException::class);

    @unlink($path);
});

it('returns 404 for direct storage proof path access', function () {
    Storage::disk('local')->put('invoices/1/secret-proof.jpg', 'fake');

    $this->get('/storage/invoices/1/secret-proof.jpg')->assertNotFound();
    $this->get('/storage/app/private/invoices/1/secret-proof.jpg')->assertNotFound();
});

it('exports then imports a competition with matching related counts', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(3, lanes: 6);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $heat = Heat::query()->where('event_id', $event->id)->firstOrFail();
    $judge = User::factory()->juri()->create();
    $event->judges()->attach($judge->id);
    $admin = User::factory()->panitia()->create();
    foreach (HeatLane::query()->where('heat_id', $heat->id)->whereNotNull('registration_id')->get() as $lane) {
        app(RecordLaneResult::class)->handle($lane, ['status' => ResultStatus::Ok, 'time_ms' => 40_000], $judge);
    }
    app(LockHeat::class)->handle($heat->fresh(), $judge);
    app(VerifyHeatResults::class)->handleHeat($heat->fresh(), $admin);
    $competition->update(['status' => CompetitionStatus::Finished]);
    app(PublishResults::class)->handle($competition->fresh(), $admin);

    Artisan::call('competition:export', ['competition' => $competition->id]);
    $files = Storage::disk('local')->files('backups');
    expect($files)->not->toBeEmpty();
    $exportPath = end($files);

    $before = [
        'registrations' => $competition->registrations()->count(),
        'heats' => Heat::query()->whereHas('event', fn ($q) => $q->where('competition_id', $competition->id))->count(),
        'results' => \App\Models\Result::query()->whereHas('heatLane.heat.event', fn ($q) => $q->where('competition_id', $competition->id))->count(),
    ];

    Artisan::call('competition:import', ['path' => $exportPath]);
    $restored = Competition::query()->where('id', '!=', $competition->id)->latest('id')->firstOrFail();

    expect($restored->registrations()->count())->toBe($before['registrations'])
        ->and(Heat::query()->whereHas('event', fn ($q) => $q->where('competition_id', $restored->id))->count())->toBe($before['heats'])
        ->and(\App\Models\Result::query()->whereHas('heatLane.heat.event', fn ($q) => $q->where('competition_id', $restored->id))->count())->toBe($before['results']);
});

it('reports database and queue health', function () {
    $this->getJson(route('health'))
        ->assertOk()
        ->assertJsonPath('database', 'ok')
        ->assertJsonStructure(['status', 'database', 'queue']);
});
