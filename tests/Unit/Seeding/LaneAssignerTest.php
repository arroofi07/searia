<?php

use App\Exceptions\UnsupportedLaneCountException;
use App\Models\Registration;
use App\Services\Seeding\LaneAssigner;

function laneEntrants(int $count): array
{
    return array_map(function (int $id): Registration {
        $registration = new Registration;
        $registration->id = $id;

        return $registration;
    }, range(1, $count));
}

it('assigns five entrants in a six-lane pool to lanes 3, 4, 2, 5, 1', function () {
    $assignment = app(LaneAssigner::class)->assign(laneEntrants(5), 6);

    expect(array_keys($assignment))->toBe([1, 2, 3, 4, 5])
        ->and($assignment[3]->id)->toBe(1)
        ->and($assignment[4]->id)->toBe(2)
        ->and($assignment[2]->id)->toBe(3)
        ->and($assignment[5]->id)->toBe(4)
        ->and($assignment[1]->id)->toBe(5);
});

it('assigns eight entrants in an eight-lane pool to center-out lanes', function () {
    $assignment = app(LaneAssigner::class)->assign(laneEntrants(8), 8);

    expect($assignment[4]->id)->toBe(1)
        ->and($assignment[5]->id)->toBe(2)
        ->and($assignment[3]->id)->toBe(3)
        ->and($assignment[6]->id)->toBe(4)
        ->and($assignment[2]->id)->toBe(5)
        ->and($assignment[7]->id)->toBe(6)
        ->and($assignment[1]->id)->toBe(7)
        ->and($assignment[8]->id)->toBe(8);
});

it('places a single entrant in lane 3 of a six-lane pool', function () {
    $assignment = app(LaneAssigner::class)->assign(laneEntrants(1), 6);

    expect($assignment)->toHaveCount(1)
        ->and($assignment[3]->id)->toBe(1);
});

it('rejects an unsupported lane count', function () {
    app(LaneAssigner::class)->assign(laneEntrants(1), 7);
})->throws(UnsupportedLaneCountException::class);
