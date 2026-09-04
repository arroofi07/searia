<?php

use App\Rules\ReasonableSwimTime;

it('accepts swim times on the lower bound, upper bound, and in between for each distance', function (int $distance, int $ms, bool $outOfRange) {
    $rule = new ReasonableSwimTime($distance);

    expect($rule->isOutOfRange($ms))->toBe($outOfRange);
})->with([
    [25, 10_000, false],
    [25, 150_000, false],
    [25, 300_000, false],
    [25, 9_000, true],
    [25, 301_000, true],
    [50, 20_000, false],
    [50, 200_000, false],
    [50, 480_000, false],
    [50, 5_200, true],
    [50, 481_000, true],
    [100, 45_000, false],
    [100, 400_000, false],
    [100, 900_000, false],
    [100, 44_000, true],
    [100, 901_000, true],
]);

it('always allows a null NT value', function () {
    $failed = false;
    (new ReasonableSwimTime(50))->validate('seed_time', null, function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('does not reject out-of-range values when used as a warning for results', function () {
    $failed = false;
    (new ReasonableSwimTime(50, rejectOutOfRange: false))->validate('time', 5_200, function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse()
        ->and((new ReasonableSwimTime(50, false))->isOutOfRange(5_200))->toBeTrue();
});
