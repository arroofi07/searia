<?php

use App\Actions\RunSeeding;
use App\Enums\CompetitionStatus;
use App\Models\ActivityLog;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\User;

it('explains seeding in plain Indonesian for panitia', function () {
    $meet = openRegistrationMeet();

    $this->actingAs(User::factory()->panitia()->create())
        ->get(route('admin.seeding.index', $meet['competition']))
        ->assertOk()
        ->assertSee('Pembagian seri dan lintasan')
        ->assertSee('catatan waktu saat daftar')
        ->assertSee('lintasan tengah')
        ->assertSee('Yang harus panitia kerjakan')
        ->assertSee('Bagi seri seluruh kejuaraan');
});

it('lets panitia filter seeding rows by status', function () {
    $meet = openRegistrationMeet();

    $this->actingAs(User::factory()->panitia()->create())
        ->get(route('admin.seeding.index', [$meet['competition'], 'status' => 'locked']))
        ->assertOk()
        ->assertSee('Tidak ada baris yang cocok dengan saringan');
});

it('locks every heat for a competition', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(6);
    app(RunSeeding::class)->handle($competition, $event, $group);

    $this->actingAs(User::factory()->panitia()->create())
        ->post(route('admin.seeding.lock', $competition))
        ->assertRedirect();

    expect(Heat::query()->where('event_id', $event->id)->whereNull('locked_at')->count())->toBe(0);
});

it('locks a competition even when leftover events have no swimmers', function () {
    $meet = openRegistrationMeet();

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.seeding.index', $meet['competition']))
        ->post(route('admin.seeding.lock', $meet['competition']))
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors('seeding');
});

it('rejects locking when a verified event still has no heats', function () {
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.seeding.index', $meet['competition']))
        ->post(route('admin.seeding.lock', $meet['competition']))
        ->assertRedirect(route('admin.seeding.index', $meet['competition']))
        ->assertSessionHasErrors('seeding');

    expect(session('errors')->first('seeding'))->toContain('belum diseeding')
        ->and(session('pending_seeding'))->toHaveCount(1)
        ->and(session('pending_seeding')[0]['event_name'])->toBe($meet['event']->formattedName())
        ->and(session('pending_seeding')[0]['age_group_name'])->toBe($meet['group']->name);
});

it('treats empty age groups as skippable instead of unseeded', function () {
    $meet = openRegistrationMeet();
    $meet['competition']->update(['status' => CompetitionStatus::Closed]);

    $this->actingAs(User::factory()->panitia()->create())
        ->get(route('admin.seeding.index', $meet['competition']))
        ->assertOk()
        ->assertSee('Tidak ada peserta')
        ->assertSee('Tidak ada seri yang perlu dikunci')
        ->assertDontSee('Langkah berikutnya: bagi seri');
});

it('swaps two lanes and writes complementary audit entries', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(6);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $lanes = HeatLane::query()->orderBy('lane_number')->get();
    $left = $lanes->firstWhere('lane_number', 3);
    $right = $lanes->firstWhere('lane_number', 4);
    $leftRegistration = $left->registration_id;
    $rightRegistration = $right->registration_id;

    $this->actingAs(User::factory()->panitia()->create())
        ->post(route('admin.heat-lanes.swap'), [
            'left_lane_id' => $left->id,
            'right_lane_id' => $right->id,
        ])
        ->assertRedirect();

    expect($left->fresh()->registration_id)->toBe($rightRegistration)
        ->and($right->fresh()->registration_id)->toBe($leftRegistration)
        ->and(ActivityLog::query()->where('action', 'heat_lane.swap')->count())->toBe(2);
});

it('lets panitia swap athletes across different heats', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(8);
    app(RunSeeding::class)->handle($competition, $event, $group);

    $heats = Heat::query()->where('event_id', $event->id)->orderBy('heat_number')->get();
    expect($heats)->toHaveCount(2);

    $fromFirst = $heats[0]->lanes()->whereNotNull('registration_id')->orderBy('lane_number')->firstOrFail();
    $fromSecond = $heats[1]->lanes()->whereNotNull('registration_id')->orderBy('lane_number')->firstOrFail();
    $leftRegistration = $fromFirst->registration_id;
    $rightRegistration = $fromSecond->registration_id;

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.seeding.show', [$competition, $event, $group]))
        ->post(route('admin.heat-lanes.swap'), [
            'left_lane_id' => $fromFirst->id,
            'right_lane_id' => $fromSecond->id,
        ])
        ->assertRedirect(route('admin.seeding.show', [$competition, $event, $group]))
        ->assertSessionHas('status', 'Peserta ditukar.');

    expect($fromFirst->fresh()->registration_id)->toBe($rightRegistration)
        ->and($fromSecond->fresh()->registration_id)->toBe($leftRegistration)
        ->and($fromFirst->fresh()->heat_id)->toBe($heats[0]->id)
        ->and($fromSecond->fresh()->heat_id)->toBe($heats[1]->id)
        ->and($fromFirst->fresh()->lane_number)->toBe($fromFirst->lane_number)
        ->and($fromSecond->fresh()->lane_number)->toBe($fromSecond->lane_number);
});

it('offers a cross-heat swap form on the seeding detail page', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(8);
    app(RunSeeding::class)->handle($competition, $event, $group);

    $this->actingAs(User::factory()->panitia()->create())
        ->get(route('admin.seeding.show', [$competition, $event, $group]))
        ->assertOk()
        ->assertSee('Tukar dua peserta')
        ->assertSee('antar seri yang berbeda')
        ->assertSee('Seri 1')
        ->assertSee('Seri 2');
});

it('rejects swapping lanes from a different age group', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(6);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $source = HeatLane::query()->whereNotNull('registration_id')->firstOrFail();

    $otherGroup = $competition->ageGroups()->where('id', '!=', $group->id)->first()
        ?? $competition->ageGroups()->create([
            'code' => 'X',
            'name' => 'Grup X',
            'display_code' => 'X',
            'birth_year_start' => 2000,
            'birth_year_end' => 2001,
            'sort_order' => 99,
        ]);

    $foreignHeat = Heat::query()->create([
        'event_id' => $event->id,
        'age_group_id' => $otherGroup->id,
        'heat_number' => 1,
        'round' => 'final',
    ]);
    $foreignLane = HeatLane::factory()->create([
        'heat_id' => $foreignHeat->id,
        'lane_number' => 3,
        'registration_id' => \App\Models\Registration::factory()->create([
            'competition_id' => $competition->id,
            'event_id' => $event->id,
            'age_group_id' => $otherGroup->id,
        ])->id,
    ]);

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.seeding.show', [$competition, $event, $group]))
        ->post(route('admin.heat-lanes.swap'), [
            'left_lane_id' => $source->id,
            'right_lane_id' => $foreignLane->id,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('heat_lane');
});

it('rejects moving an entrant onto an occupied lane', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(6);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $heat = Heat::query()->where('event_id', $event->id)->first();
    $source = $heat->lanes()->where('lane_number', 3)->first();

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.seeding.show', [$competition, $event, $group]))
        ->post(route('admin.heat-lanes.move', $source), [
            'target_heat_id' => $heat->id,
            'target_lane_number' => 4,
        ])
        ->assertRedirect(route('admin.seeding.show', [$competition, $event, $group]))
        ->assertSessionHasErrors('heat_lane');
});

it('withdraws an entrant without shifting other lanes', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(6);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $heat = Heat::query()->where('event_id', $event->id)->firstOrFail();
    $lane3 = $heat->lanes()->where('lane_number', 3)->firstOrFail();
    $lane4 = $heat->lanes()->where('lane_number', 4)->firstOrFail();
    $keepRegistration = $lane4->registration_id;

    $this->actingAs(User::factory()->panitia()->create())
        ->delete(route('admin.heat-lanes.withdraw', $lane3))
        ->assertRedirect();

    expect($lane3->fresh()->registration_id)->toBeNull()
        ->and($lane4->fresh()->registration_id)->toBe($keepRegistration)
        ->and($heat->lanes()->count())->toBe(6)
        ->and(ActivityLog::query()->where('action', 'heat_lane.withdraw')->count())->toBe(1);
});

it('shows a locked meet as incomplete when a verified swimmer joined after seeding', function () {
    [$competition, $event, $group, $registrations] = seedMeetWithEntrants(3);
    app(RunSeeding::class)->handle($competition, $event, $group)->each->lock();
    $competition->update(['status' => CompetitionStatus::Closed]);

    $newAthlete = \App\Models\Athlete::factory()->create([
        'club_id' => $registrations[0]->athlete->club_id,
        'gender' => $registrations[0]->athlete->gender,
        'birth_year' => 2016,
        'full_name' => 'PESERTA FORM BARU',
    ]);
    verifiedRegistration([
        'competition' => $competition,
        'event' => $event,
        'athlete' => $newAthlete,
        'group' => $group,
        'panitia' => User::factory()->panitia()->create(),
    ]);

    $this->actingAs(User::factory()->panitia()->create())
        ->get(route('admin.seeding.index', $competition))
        ->assertOk()
        ->assertSee('Ada peserta yang belum masuk seri')
        ->assertSee('Peserta belum masuk seri')
        ->assertSee('3/4')
        ->assertSee('belum masuk seri')
        ->assertDontSee('Semua seri terkunci')
        ->assertDontSee('Tidak ada seri yang perlu dikunci');

    $this->actingAs(User::factory()->panitia()->create())
        ->get(route('admin.seeding.show', [$competition, $event, $group]))
        ->assertOk()
        ->assertSee('PESERTA FORM BARU')
        ->assertSee('peserta disetujui belum masuk seri');
});

it('does not treat a meet as fully seeded while a form entry is still pending', function () {
    [$competition, $event, $group, $registrations] = seedMeetWithEntrants(3);
    app(RunSeeding::class)->handle($competition, $event, $group)->each->lock();
    $competition->update(['status' => CompetitionStatus::Closed]);

    $newAthlete = \App\Models\Athlete::factory()->create([
        'club_id' => $registrations[0]->athlete->club_id,
        'gender' => $registrations[0]->athlete->gender,
        'birth_year' => 2016,
        'full_name' => 'MENUNGGU VERIFIKASI',
    ]);
    \App\Models\Registration::factory()->create([
        'competition_id' => $competition->id,
        'event_id' => $event->id,
        'athlete_id' => $newAthlete->id,
        'age_group_id' => $group->id,
        'status' => \App\Enums\RegistrationStatus::Pending,
    ]);

    $this->actingAs(User::factory()->panitia()->create())
        ->get(route('admin.seeding.index', $competition))
        ->assertOk()
        ->assertSee('belum disetujui')
        ->assertSee('Ada pendaftar baru')
        ->assertSee('menunggu verifikasi')
        ->assertDontSee('Semua seri terkunci')
        ->assertDontSee('Tidak ada seri yang perlu dikunci');
});

it('rejects locking when heats exist but a verified swimmer is still off the lanes', function () {
    [$competition, $event, $group, $registrations] = seedMeetWithEntrants(3);
    app(RunSeeding::class)->handle($competition, $event, $group);

    $newAthlete = \App\Models\Athlete::factory()->create([
        'club_id' => $registrations[0]->athlete->club_id,
        'gender' => $registrations[0]->athlete->gender,
        'birth_year' => 2016,
        'full_name' => 'TERLAMBAT DISETUJUI',
    ]);
    verifiedRegistration([
        'competition' => $competition,
        'event' => $event,
        'athlete' => $newAthlete,
        'group' => $group,
        'panitia' => User::factory()->panitia()->create(),
    ]);

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.seeding.index', $competition))
        ->post(route('admin.seeding.lock', $competition))
        ->assertRedirect(route('admin.seeding.index', $competition))
        ->assertSessionHasErrors('seeding');

    expect(session('errors')->first('seeding'))->toContain('belum diseeding')
        ->and(session('pending_seeding'))->toHaveCount(1)
        ->and(Heat::query()->where('event_id', $event->id)->whereNull('locked_at')->count())->toBeGreaterThan(0);
});

it('rejects moving across a different age group', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(6);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $heat = Heat::query()->where('event_id', $event->id)->firstOrFail();
    $source = $heat->lanes()->whereNotNull('registration_id')->firstOrFail();

    $otherGroup = $competition->ageGroups()->where('id', '!=', $group->id)->first()
        ?? $competition->ageGroups()->create([
            'code' => 'X',
            'name' => 'Grup X',
            'display_code' => 'X',
            'birth_year_start' => 2000,
            'birth_year_end' => 2001,
            'sort_order' => 99,
        ]);

    $foreignHeat = Heat::query()->create([
        'event_id' => $event->id,
        'age_group_id' => $otherGroup->id,
        'heat_number' => 1,
        'round' => 'final',
    ]);

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.seeding.show', [$competition, $event, $group]))
        ->post(route('admin.heat-lanes.move', $source), [
            'target_heat_id' => $foreignHeat->id,
            'target_lane_number' => 3,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('heat_lane');
});
