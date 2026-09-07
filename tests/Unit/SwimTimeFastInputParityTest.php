<?php

use App\Support\SwimTime;

it('matches php and documented fast digit inputs', function (string $input, int $expected) {
    expect(SwimTime::parse($input, true)?->milliseconds)->toBe($expected);
})->with([
    ['5220', 52_200],
    ['3470', 34_700],
    ['13470', 94_700],
    ['013470', 94_700],
]);
