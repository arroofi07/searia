<?php

use App\Enums\DisqualificationCode;
use App\Enums\ResultStatus;

it('keeps official result abbreviations and adds Indonesian explanations', function () {
    expect(ResultStatus::Dns->label())->toBe('DNS')
        ->and(ResultStatus::Dns->description())->toBe('Tidak start (Did Not Start)')
        ->and(ResultStatus::Dns->optionLabel())->toBe('DNS · Tidak start (Did Not Start)')
        ->and(ResultStatus::Dsq->optionLabel())->toContain('Diskualifikasi');
});

it('explains each DSQ code beyond the abbreviation', function () {
    expect(DisqualificationCode::Sf->value)->toBe('SF')
        ->and(DisqualificationCode::Sf->description())->toBe('Start mendahului aba-aba')
        ->and(DisqualificationCode::Sf->optionLabel())->toBe('SF · Start mendahului aba-aba');
});
