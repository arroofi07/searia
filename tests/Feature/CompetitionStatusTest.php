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
        ->assertSee('Hanya perpindahan berurutan yang diizinkan. Mundur hanya untuk Super Admin.')
        ->assertSee('Lanjut ke Sudah diseeding')
        ->assertSee('status-forward-modal', false)
        ->assertSee('Batal');
});
