<?php

use App\Models\Competition;
use App\Models\User;

it('does not loop competition scoped sidebar links back to the dashboard when no event exists', function () {
    $panitia = User::factory()->panitia()->create();

    expect(Competition::query()->count())->toBe(0);

    $this->actingAs($panitia)
        ->get(route('admin.competitions.index'))
        ->assertOk()
        ->assertSee('Belum ada acara')
        ->assertSee(route('admin.imports.entry'), false)
        ->assertSee('title="Buat acara terlebih dahulu lewat Dasbor"', false);

    $this->actingAs($panitia)
        ->get(route('admin.imports.entry'))
        ->assertOk()
        ->assertSee('Buat acara terlebih dahulu')
        ->assertSee('NOMOR LOMBA');
});

it('activates competition scoped sidebar links when an event exists', function () {
    $meet = openRegistrationMeet();
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->get(route('admin.competitions.index'))
        ->assertOk()
        ->assertSee(route('admin.imports.entry'), false)
        ->assertSee(route('admin.registrations.index', $meet['competition']), false);

    $this->actingAs($panitia)
        ->get(route('admin.imports.entry'))
        ->assertRedirect(route('admin.imports.index', $meet['competition']));
});
