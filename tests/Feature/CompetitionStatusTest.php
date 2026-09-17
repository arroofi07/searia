<?php

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\User;

it('returns 403 when a committee member tries to roll a status backward', function () {
    $competition = Competition::factory()->status(CompetitionStatus::Registration)->create();
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->patch(route('admin.competitions.status', $competition), [
            'status' => CompetitionStatus::Draft->value,
            'reason' => 'Ingin memperbaiki nomor lomba',
        ])
        ->assertForbidden();

    expect($competition->fresh()->status)->toBe(CompetitionStatus::Registration);
});

it('asks for confirmation before moving status forward', function () {
    $competition = Competition::factory()->status(CompetitionStatus::Closed)->create();

    $this->actingAs(User::factory()->panitia()->create())
        ->get(route('admin.competitions.show', $competition))
        ->assertOk()
        ->assertSee('Ubah status')
        ->assertSee('Lanjut ke Sudah diseeding')
        ->assertSee('status-forward-modal', false)
        ->assertSee('Batal')
        ->assertDontSee('Kembalikan ke Pendaftaran ditutup');
});

it('lets panitia return a seeded meet to closed so seeding can be redone', function () {
    $competition = Competition::factory()->status(CompetitionStatus::Seeded)->create();
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->get(route('admin.competitions.show', $competition))
        ->assertOk()
        ->assertSee('Kembalikan ke Pendaftaran ditutup')
        ->assertSee('status-back-modal', false);

    $this->actingAs($panitia)
        ->patch(route('admin.competitions.status', $competition), [
            'status' => CompetitionStatus::Closed->value,
            'reason' => 'Ada peserta baru yang belum masuk seri',
        ])
        ->assertRedirect();

    expect($competition->fresh()->status)->toBe(CompetitionStatus::Closed);
});

it('requires a reason when panitia reverts seeded to closed', function () {
    $competition = Competition::factory()->status(CompetitionStatus::Seeded)->create();

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.competitions.show', $competition))
        ->patch(route('admin.competitions.status', $competition), [
            'status' => CompetitionStatus::Closed->value,
        ])
        ->assertRedirect(route('admin.competitions.show', $competition))
        ->assertSessionHasErrors('reason');

    expect($competition->fresh()->status)->toBe(CompetitionStatus::Seeded);
});

it('lets panitia move to seeded when leftover events have no swimmers', function () {
    $meet = openRegistrationMeet();
    $meet['competition']->update(['status' => CompetitionStatus::Closed]);

    $this->actingAs(User::factory()->panitia()->create())
        ->patch(route('admin.competitions.status', $meet['competition']), [
            'status' => CompetitionStatus::Seeded->value,
        ])
        ->assertRedirect();

    expect($meet['competition']->fresh()->status)->toBe(CompetitionStatus::Seeded);
});

it('lists the unseeded event when moving to seeded is rejected', function () {
    $meet = openRegistrationMeet();
    $meet['competition']->update(['status' => CompetitionStatus::Closed]);
    verifiedRegistration($meet);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->from(route('admin.competitions.show', $meet['competition']))
        ->patch(route('admin.competitions.status', $meet['competition']), [
            'status' => CompetitionStatus::Seeded->value,
        ])
        ->assertRedirect(route('admin.competitions.show', $meet['competition']))
        ->assertSessionHasErrors('status');

    expect(session('errors')->first('status'))->toContain('belum diseeding')
        ->and(session('pending_seeding'))->toHaveCount(1)
        ->and(session('pending_seeding')[0]['event_name'])->toBe($meet['event']->formattedName())
        ->and(session('pending_seeding')[0]['age_group_name'])->toBe($meet['group']->name);

    $this->actingAs($panitia)
        ->get(route('admin.competitions.show', $meet['competition']))
        ->assertOk()
        ->assertSee('Belum bisa ke Sudah diseeding')
        ->assertSee($meet['event']->formattedName())
        ->assertSee($meet['group']->name)
        ->assertSee('Bagi seri ini')
        ->assertSee('Buka semua yang belum dibagi');
});
