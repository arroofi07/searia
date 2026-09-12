<?php

use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\Stroke;
use App\Models\AgeGroup;
use App\Services\EventProgram\EventProgramParser;

it('parses official event names including equipment', function () {
    $parser = new EventProgramParser;

    expect($parser->parseName('50 M Gaya Kupu-Kupu'))->toMatchArray([
        'distance' => 50,
        'stroke' => Stroke::Butterfly,
        'equipment' => Equipment::None,
    ])
        ->and($parser->parseName('50 M GAYA KUPU-KUPU (FINS)'))->toMatchArray([
            'distance' => 50,
            'stroke' => Stroke::Butterfly,
            'equipment' => Equipment::Fins,
        ])
        ->and($parser->parseName('25 M Gaya Bebas (Kickboard)'))->toMatchArray([
            'distance' => 25,
            'stroke' => Stroke::Freestyle,
            'equipment' => Equipment::Kickboard,
        ])
        ->and($parser->parseName('50 M BEBAS (FINS)'))->toMatchArray([
            'distance' => 50,
            'stroke' => Stroke::Freestyle,
            'equipment' => Equipment::Fins,
        ]);
});

it('parses putra and putri labels', function () {
    $parser = new EventProgramParser;

    expect($parser->parseGender('Putra'))->toBe(EventGender::Male)
        ->and($parser->parseGender('PA'))->toBe(EventGender::Male)
        ->and($parser->parseGender('Putri'))->toBe(EventGender::Female)
        ->and($parser->parseGender('PI'))->toBe(EventGender::Female);
});

it('resolves group names and codes', function () {
    $parser = new EventProgramParser;
    $groups = collect([
        (new AgeGroup)->forceFill(['id' => 10, 'name' => 'Group 1', 'code' => '1', 'display_code' => 'I']),
        (new AgeGroup)->forceFill(['id' => 11, 'name' => 'Group 2', 'code' => '2', 'display_code' => 'II']),
    ]);

    $parsed = $parser->parseGroups('Group 1, Group 2', $groups);

    expect($parsed['ids'])->toEqualCanonicalizing([10, 11])
        ->and($parsed['unknown'])->toBeEmpty();

    $unknown = $parser->parseGroups('Group 9', $groups);

    expect($unknown['unknown'])->toBe(['Group 9']);
});
