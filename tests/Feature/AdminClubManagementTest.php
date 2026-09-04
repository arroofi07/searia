<?php

use App\Enums\ClubStatus;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\User;

it('changes club status to verified when the committee approves it', function () {
    $club = Club::factory()->pending()->create();
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->patch(route('admin.clubs.verify', $club))
        ->assertRedirect();

    expect($club->fresh()->status)->toBe(ClubStatus::Verified)
        ->and($club->fresh()->rejection_reason)->toBeNull();
});

it('requires a reason when rejecting a club', function () {
    $club = Club::factory()->pending()->create();
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->from(route('admin.clubs.show', $club))
        ->patch(route('admin.clubs.reject', $club), [])
        ->assertRedirect(route('admin.clubs.show', $club))
        ->assertSessionHasErrors('rejection_reason');

    expect($club->fresh()->status)->toBe(ClubStatus::Pending);
});

it('rejects deleting a club that already has athletes and deactivates it instead', function () {
    $club = Club::factory()->create();
    Athlete::factory()->create(['club_id' => $club->id]);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->from(route('admin.clubs.show', $club))
        ->delete(route('admin.clubs.destroy', $club))
        ->assertRedirect(route('admin.clubs.show', $club))
        ->assertSessionHasErrors([
            'delete' => 'Klub yang sudah memiliki atlet tidak dapat dihapus, hanya dapat dinonaktifkan.',
        ]);

    expect(Club::query()->find($club->id))->not->toBeNull()
        ->and($club->fresh()->is_active)->toBeFalse();
});

it('lists clubs filtered by name status and city', function () {
    Club::factory()->create(['name' => 'SeaRIA Aquatic Padang', 'city' => 'Padang', 'status' => ClubStatus::Verified]);
    Club::factory()->pending()->create(['name' => 'Angkasa Swimming Club', 'city' => 'Medan']);

    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->get(route('admin.clubs.index', [
            'search' => 'searia',
            'status' => ClubStatus::Verified->value,
            'city' => 'Padang',
        ]))
        ->assertOk()
        ->assertSee('SeaRIA Aquatic Padang')
        ->assertDontSee('Angkasa Swimming Club');
});
