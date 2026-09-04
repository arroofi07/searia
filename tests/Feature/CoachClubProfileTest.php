<?php

use App\Enums\ClubStatus;
use App\Enums\ClubType;
use App\Models\Club;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('returns 403 when a coach opens another club profile', function () {
    $clubA = Club::factory()->create();
    $clubB = Club::factory()->create();
    $coach = User::factory()->pelatih($clubA)->create();

    $this->actingAs($coach)
        ->get(route('coach.club.show', $clubB))
        ->assertForbidden();
});

it('sends a verified club back to pending when the coach changes its name', function () {
    $club = Club::factory()->create([
        'name' => 'SeaRIA Aquatic Padang',
        'status' => ClubStatus::Verified,
        'type' => ClubType::Perkumpulan,
        'city' => 'Padang',
    ]);
    $coach = User::factory()->pelatih($club)->create();

    $this->actingAs($coach)
        ->put(route('coach.club.update', $club), [
            'name' => 'SeaRIA Aquatic',
            'short_name' => $club->short_name,
            'type' => ClubType::Perkumpulan->value,
            'city' => 'Padang',
            'province' => $club->province,
            'contact_name' => $club->contact_name,
            'contact_phone' => $club->contact_phone,
        ])
        ->assertRedirect(route('coach.club.show', $club));

    expect($club->fresh()->status)->toBe(ClubStatus::Pending)
        ->and($club->fresh()->name)->toBe('SeaRIA Aquatic');
});

it('ignores a status field submitted by a coach', function () {
    $club = Club::factory()->pending()->create();
    $coach = User::factory()->pelatih($club)->create();

    $this->actingAs($coach)
        ->put(route('coach.club.update', $club), [
            'name' => $club->name,
            'short_name' => $club->short_name,
            'type' => $club->type->value,
            'city' => $club->city,
            'province' => $club->province,
            'contact_name' => $club->contact_name,
            'contact_phone' => $club->contact_phone,
            'status' => ClubStatus::Verified->value,
        ])
        ->assertRedirect(route('coach.club.show', $club));

    expect($club->fresh()->status)->toBe(ClubStatus::Pending);
});

it('stores a club logo that is a jpg under two megabytes', function () {
    Storage::fake('public');

    $club = Club::factory()->create();
    $coach = User::factory()->pelatih($club)->create();

    $this->actingAs($coach)
        ->put(route('coach.club.update', $club), [
            'name' => $club->name,
            'short_name' => $club->short_name,
            'type' => $club->type->value,
            'city' => $club->city,
            'province' => $club->province,
            'contact_name' => $club->contact_name,
            'contact_phone' => $club->contact_phone,
            'logo' => UploadedFile::fake()->image('logo.jpg', 120, 120),
        ])
        ->assertRedirect(route('coach.club.show', $club));

    $club->refresh();

    expect($club->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($club->logo_path);
});
