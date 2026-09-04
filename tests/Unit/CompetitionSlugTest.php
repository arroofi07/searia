<?php

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds a unique slug from a name that contains punctuation', function () {
    $competition = Competition::factory()->create([
        'name' => 'SeaRIA Championship 2026: Painan (Uji-Coba)!',
    ]);

    expect($competition->slug)->toBe('searia-championship-2026-painan-uji-coba');
});

it('appends a suffix when the generated slug is already taken', function () {
    Competition::factory()->create(['name' => 'SeaRIA Open']);
    $copy = Competition::factory()->create(['name' => 'SeaRIA Open']);

    expect($copy->slug)->toBe('searia-open-2');
});

it('scopes active competitions to those that are not published', function () {
    Competition::factory()->status(CompetitionStatus::Draft)->create();
    Competition::factory()->status(CompetitionStatus::Registration)->create();
    Competition::factory()->status(CompetitionStatus::Published)->create();

    expect(Competition::query()->active()->count())->toBe(2);
});
