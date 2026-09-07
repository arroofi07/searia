<?php

use App\Enums\SeedingMode;
use App\Models\Registration;
use App\Services\Seeding\HeatDistributor;
use Illuminate\Support\Collection;

function fakeEntrants(int $count): Collection
{
    return collect(range(1, $count))->map(function (int $id): Registration {
        $registration = new Registration;
        $registration->id = $id;

        return $registration;
    });
}

function heatSizes(array $heats): array
{
    return array_map(fn (array $heat): int => count($heat), $heats);
}

it('balances 15 entrants across 6 lanes into three heats of five', function () {
    $heats = app(HeatDistributor::class)->distribute(fakeEntrants(15), 6, SeedingMode::Balanced);

    expect(heatSizes($heats))->toBe([5, 5, 5]);
});

it('balances 17 entrants across 6 lanes into 5, 6, 6', function () {
    $heats = app(HeatDistributor::class)->distribute(fakeEntrants(17), 6, SeedingMode::Balanced);

    expect(heatSizes($heats))->toBe([5, 6, 6]);
});

it('balances 15 entrants across 8 lanes into 7 and 8', function () {
    $heats = app(HeatDistributor::class)->distribute(fakeEntrants(15), 8, SeedingMode::Balanced);

    expect(heatSizes($heats))->toBe([7, 8]);
});

it('balances 20 entrants across 8 lanes into 6, 7, 7', function () {
    $heats = app(HeatDistributor::class)->distribute(fakeEntrants(20), 8, SeedingMode::Balanced);

    expect(heatSizes($heats))->toBe([6, 7, 7]);
});

it('fills from last for 15 entrants and 6 lanes into 3, 6, 6', function () {
    $heats = app(HeatDistributor::class)->distribute(fakeEntrants(15), 6, SeedingMode::FillFromLast);

    expect(heatSizes($heats))->toBe([3, 6, 6]);
});

it('puts the fastest entrants into the highest heat number', function () {
    $heats = app(HeatDistributor::class)->distribute(fakeEntrants(15), 6, SeedingMode::Balanced);

    expect($heats[2][0]->id)->toBe(1)
        ->and($heats[0][0]->id)->toBe(11);
});
