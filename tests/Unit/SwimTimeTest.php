<?php

use App\Exceptions\InvalidSwimTimeException;
use App\Support\SwimTime;

it('parses every documented swim time input', function (string $input, ?int $expected) {
    expect(SwimTime::parse($input)?->milliseconds)->toBe($expected);
})->with([
    ['52.20', 52_200],
    ['52,20', 52_200],
    ['00:52.20', 52_200],
    ['1:34.70', 94_700],
    ['01:34.70', 94_700],
    ['00:01:34.70', 94_700],
    ['5220', 52_200],
    ['13470', 94_700],
    ['NT', null],
    ['nt', null],
    ['', null],
    ['-', null],
    ['99:99:99', null],
]);

it('formats milliseconds using the documented display rules', function () {
    expect(SwimTime::formatMilliseconds(52_200))->toBe('00:52.20')
        ->and(SwimTime::formatMilliseconds(94_700))->toBe('01:34.70')
        ->and(SwimTime::formatMilliseconds(3_735_440))->toBe('01:02:15.44')
        ->and(SwimTime::formatMilliseconds(null))->toBe('NT');
});

it('round-trips formatted values back to the same milliseconds', function () {
    foreach ([52_200, 94_700, 3_735_440] as $ms) {
        $formatted = SwimTime::formatMilliseconds($ms);
        expect(SwimTime::parse($formatted)?->milliseconds)->toBe($ms);
    }
});

it('throws when the input is not a recognized swim time', function () {
    SwimTime::parse('bukan-waktu');
})->throws(InvalidSwimTimeException::class);
