<?php

use App\Services\Import\WorkbookTimeNormalizer;

it('converts excel fractional day times to swim seed format', function () {
    $normalizer = new WorkbookTimeNormalizer;

    expect($normalizer->excelTimeToSeed(45 / 86_400))->toBe('00:45.00')
        ->and($normalizer->excelTimeToSeed(2623 / 86_400))->toBe('00:43.43')
        ->and($normalizer->excelTimeToSeed('4.02.22'))->toBe('04:02.22')
        ->and($normalizer->excelTimeToSeed(''))->toBe('');
});
