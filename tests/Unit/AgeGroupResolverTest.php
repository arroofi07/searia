<?php

use App\Exceptions\OverlappingAgeGroupsException;
use App\Models\AgeGroup;
use App\Models\Competition;
use App\Services\AgeGroupResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('matches birth years on the inclusive lower and upper bounds of each group', function () {
    $competition = Competition::factory()->create();
    AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '3',
        'birth_year_start' => 2015,
        'birth_year_end' => 2016,
    ]);

    $resolver = new AgeGroupResolver;

    expect($resolver->resolve($competition, 2015)?->code)->toBe('3')
        ->and($resolver->resolve($competition, 2016)?->code)->toBe('3');
});

it('returns null when the birth year sits outside every group', function () {
    $competition = Competition::factory()->create();
    AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '3',
        'birth_year_start' => 2015,
        'birth_year_end' => 2016,
    ]);

    expect((new AgeGroupResolver)->resolve($competition, 2014))->toBeNull()
        ->and((new AgeGroupResolver)->resolve($competition, 2017))->toBeNull();
});

it('throws when two groups overlap for the same birth year', function () {
    $competition = Competition::factory()->create();
    AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '3',
        'birth_year_start' => 2015,
        'birth_year_end' => 2016,
    ]);
    AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '4',
        'birth_year_start' => 2016,
        'birth_year_end' => 2017,
    ]);

    (new AgeGroupResolver)->resolve($competition, 2016);
})->throws(OverlappingAgeGroupsException::class);
