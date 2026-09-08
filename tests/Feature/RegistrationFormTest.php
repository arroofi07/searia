<?php

use App\Enums\ClubStatus;
use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\RegistrationStatus;
use App\Enums\Stroke;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Event;
use App\Models\Registration;
use App\Models\RegistrationSubmission;
use App\Models\User;

function submitStep1(array $meet, array $overrides = []): Illuminate\Testing\TestResponse
{
    return test()->post(route('register.athlete', $meet['competition']), array_merge([
        'registrant_name' => 'Rahmat Hidayat',
        'registrant_phone' => '081234567890',
        'registrant_email' => 'rahmat@example.test',
        'full_name' => 'AHZA DANISH RAHMAN',
        'gender' => 'L',
        'birth_year' => 2016,
        'club_name' => $meet['club']->name,
        'club_city' => $meet['club']->city ?? 'Padang',
    ], $overrides));
}

it('lets a visitor register an athlete across three steps without logging in', function () {
    $meet = openRegistrationMeet();
    $second = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 15,
        'gender' => EventGender::Male,
        'distance' => 50,
        'stroke' => Stroke::Freestyle,
    ]);
    $second->ageGroups()->attach($meet['group']->id);

    submitStep1($meet)->assertRedirect(route('register.events', $meet['competition']));

    $this->post(route('register.events.store', $meet['competition']), [
        'event_ids' => [$meet['event']->id, $second->id],
        'seed_times' => [
            $meet['event']->id => '00:52.20',
            $second->id => '',
        ],
    ])->assertRedirect(route('register.review', $meet['competition']));

    $this->get(route('register.review', $meet['competition']))->assertOk();

    $submission = null;

    $this->post(route('register.store', $meet['competition']), [
        'submit_token' => session('register_token.'.$meet['competition']->id),
        'terms' => '1',
    ])->assertRedirect();

    $submission = RegistrationSubmission::query()->firstOrFail();

    expect(Registration::query()->count())->toBe(2)
        ->and(Registration::query()->where('seed_time_ms', 52_200)->count())->toBe(1)
        ->and(Registration::query()->whereNull('seed_time_ms')->count())->toBe(1)
        ->and(Registration::query()->pluck('submission_id')->unique()->all())->toBe([$submission->id])
        ->and(Registration::query()->pluck('registered_by')->unique()->all())->toBe([null])
        ->and($submission->registrant_phone)->toBe('081234567890');
});

it('does not issue an invoice when a public registration is submitted', function () {
    $meet = openRegistrationMeet();

    submitStep1($meet);
    $this->post(route('register.events.store', $meet['competition']), [
        'event_ids' => [$meet['event']->id],
    ]);
    $this->get(route('register.review', $meet['competition']));
    $this->post(route('register.store', $meet['competition']), [
        'submit_token' => session('register_token.'.$meet['competition']->id),
        'terms' => '1',
    ]);

    $submission = RegistrationSubmission::query()->firstOrFail();

    expect($submission->invoice)->toBeNull()
        ->and($submission->registrations)->toHaveCount(1)
        ->and($submission->registrations->first()->invoice_id)->toBeNull();
});

it('creates a pending club when the registrant types one that does not exist yet', function () {
    $meet = openRegistrationMeet();

    submitStep1($meet, [
        'club_name' => 'Tirta Muda Painan',
        'club_city' => 'Pesisir Selatan',
    ]);
    $this->post(route('register.events.store', $meet['competition']), [
        'event_ids' => [$meet['event']->id],
    ]);
    $this->get(route('register.review', $meet['competition']));
    $this->post(route('register.store', $meet['competition']), [
        'submit_token' => session('register_token.'.$meet['competition']->id),
        'terms' => '1',
    ]);

    $club = Club::query()->where('name', 'Tirta Muda Painan')->firstOrFail();

    expect($club->status)->toBe(ClubStatus::Pending)
        ->and($club->city)->toBe('Pesisir Selatan')
        ->and(Athlete::query()->where('club_id', $club->id)->count())->toBe(1);
});

it('reuses an existing athlete instead of creating a duplicate', function () {
    $meet = openRegistrationMeet();

    submitStep1($meet);
    $this->post(route('register.events.store', $meet['competition']), [
        'event_ids' => [$meet['event']->id],
    ]);
    $this->get(route('register.review', $meet['competition']));
    $this->post(route('register.store', $meet['competition']), [
        'submit_token' => session('register_token.'.$meet['competition']->id),
        'terms' => '1',
    ]);

    expect(Athlete::query()->count())->toBe(1)
        ->and(Registration::query()->first()->athlete_id)->toBe($meet['athlete']->id);
});

it('rejects an event outside the age group even when posted directly over HTTP', function () {
    $meet = openRegistrationMeet();
    $ineligible = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 5,
        'gender' => EventGender::Male,
        'distance' => 50,
        'stroke' => Stroke::Butterfly,
        'equipment' => Equipment::Fins,
    ]);

    $token = 'token-ineligible';

    $this->withSession([
        'public_registration.'.$meet['competition']->id => [
            'registrant' => ['name' => 'Rahmat', 'phone' => '0812', 'email' => null],
            'athlete' => [
                'full_name' => $meet['athlete']->full_name,
                'gender' => 'L',
                'birth_year' => 2016,
                'club_name' => $meet['club']->name,
                'club_city' => $meet['club']->city ?? 'Padang',
            ],
            'event_ids' => [$ineligible->id],
            'seed_times' => [],
        ],
        'register_token.'.$meet['competition']->id => $token,
    ])->post(route('register.store', $meet['competition']), [
        'submit_token' => $token,
        'terms' => '1',
    ])->assertSessionHasErrors('events');

    expect(Registration::query()->count())->toBe(0)
        ->and(RegistrationSubmission::query()->count())->toBe(0);
});

it('refuses registration once the competition is no longer open', function () {
    $meet = openRegistrationMeet();
    $meet['competition']->update(['status' => App\Enums\CompetitionStatus::Closed]);

    $this->get(route('register.create', $meet['competition']))->assertForbidden();
    submitStep1($meet)->assertForbidden();
});

it('blocks a submission that filled the honeypot field', function () {
    $meet = openRegistrationMeet();

    submitStep1($meet, ['website' => 'https://spam.example'])
        ->assertSessionHasErrors('website');
});

it('lists open competitions on the public landing page', function () {
    $meet = openRegistrationMeet();

    $this->get(route('register.index'))
        ->assertOk()
        ->assertSee($meet['competition']->name);
});

it('shows the receipt only to the browser that just submitted', function () {
    $meet = openRegistrationMeet();

    submitStep1($meet);
    $this->post(route('register.events.store', $meet['competition']), [
        'event_ids' => [$meet['event']->id],
    ]);
    $this->get(route('register.review', $meet['competition']));
    $this->post(route('register.store', $meet['competition']), [
        'submit_token' => session('register_token.'.$meet['competition']->id),
        'terms' => '1',
    ]);

    $submission = RegistrationSubmission::query()->firstOrFail();

    $this->get(route('register.done', $submission->code))
        ->assertOk()
        ->assertSee($submission->code);

    // Sesi lain tidak boleh menebak kode orang lain untuk melihat kontak pendaftar.
    $this->flushSession();
    $this->get(route('register.done', $submission->code))->assertNotFound();
});

it('lets panitia find a submission by code and read the registrant contact', function () {
    $meet = openRegistrationMeet();
    $submission = RegistrationSubmission::factory()->create([
        'competition_id' => $meet['competition']->id,
        'athlete_id' => $meet['athlete']->id,
        'registrant_name' => 'Rahmat Hidayat',
        'registrant_phone' => '081234567890',
    ]);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->get(route('admin.submissions.index', [$meet['competition'], 'code' => $submission->code]))
        ->assertOk()
        ->assertSee('Rahmat Hidayat');

    $this->actingAs($panitia)
        ->get(route('admin.submissions.show', $submission))
        ->assertOk()
        ->assertSee('081234567890');

    $this->actingAs(User::factory()->juri()->create())
        ->get(route('admin.submissions.show', $submission))
        ->assertForbidden();
});

it('keeps entries pending until panitia verifies them', function () {
    $meet = openRegistrationMeet();

    submitStep1($meet);
    $this->post(route('register.events.store', $meet['competition']), [
        'event_ids' => [$meet['event']->id],
    ]);
    $this->get(route('register.review', $meet['competition']));
    $this->post(route('register.store', $meet['competition']), [
        'submit_token' => session('register_token.'.$meet['competition']->id),
        'terms' => '1',
    ]);

    expect(Registration::query()->first()->status)->toBe(RegistrationStatus::Pending);
});
