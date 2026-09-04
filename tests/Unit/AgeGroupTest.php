<?php

use App\Models\AgeGroup;
use App\Models\Competition;
use App\Rules\NonOverlappingBirthYearRange;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects overlapping birth year ranges in the same competition', function () {
    $competition = Competition::factory()->create();
    AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '3',
        'name' => 'Group 3',
        'birth_year_start' => 2015,
        'birth_year_end' => 2016,
    ]);

    $failed = false;
    $rule = new NonOverlappingBirthYearRange($competition->id, 2016);
    $rule->validate('birth_year_end', 2017, function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

it('finds exactly one age group for a birth year', function () {
    $competition = Competition::factory()->create();
    AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '3',
        'birth_year_start' => 2015,
        'birth_year_end' => 2016,
        'sort_order' => 3,
    ]);
    AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '4',
        'birth_year_start' => 2013,
        'birth_year_end' => 2014,
        'sort_order' => 4,
    ]);

    $matched = AgeGroup::query()
        ->where('competition_id', $competition->id)
        ->containingBirthYear(2016)
        ->get();

    expect($matched)->toHaveCount(1)
        ->and($matched->first()->code)->toBe('3');
});
