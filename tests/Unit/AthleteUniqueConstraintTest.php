<?php

use App\Models\Athlete;
use App\Models\Club;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows the same athlete name in different clubs', function () {
    $clubA = Club::factory()->create();
    $clubB = Club::factory()->create();

    Athlete::factory()->create([
        'club_id' => $clubA->id,
        'full_name' => 'AHZA DANISH RAHMAN',
        'birth_year' => 2016,
    ]);

    Athlete::factory()->create([
        'club_id' => $clubB->id,
        'full_name' => 'AHZA DANISH RAHMAN',
        'birth_year' => 2016,
    ]);

    expect(Athlete::query()->count())->toBe(2);
});

it('filters athletes with the birth year range scope', function () {
    $club = Club::factory()->create();

    Athlete::factory()->create(['club_id' => $club->id, 'full_name' => 'ALPHA', 'birth_year' => 2010]);
    Athlete::factory()->create(['club_id' => $club->id, 'full_name' => 'BRAVO', 'birth_year' => 2016]);
    Athlete::factory()->create(['club_id' => $club->id, 'full_name' => 'CHARLIE', 'birth_year' => 2018]);

    $matched = Athlete::query()->byBirthYearRange(2015, 2017)->pluck('full_name');

    expect($matched->all())->toBe(['BRAVO']);
});

it('rejects the same name and birth year inside one club', function () {
    $club = Club::factory()->create();

    Athlete::factory()->create([
        'club_id' => $club->id,
        'full_name' => 'AHZA DANISH RAHMAN',
        'birth_year' => 2016,
    ]);

    Athlete::factory()->create([
        'club_id' => $club->id,
        'full_name' => 'AHZA DANISH RAHMAN',
        'birth_year' => 2016,
    ]);
})->throws(UniqueConstraintViolationException::class);
