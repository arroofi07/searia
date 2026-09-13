<?php

use App\Services\AgeGroupImport\AgeGroupImportParser;

it('parses age group cells', function () {
    $parser = new AgeGroupImportParser;

    expect($parser->parseCode('1'))->toBe('1')
        ->and($parser->parseCode('Searia1'))->toBe('Searia1')
        ->and($parser->parseCode(''))->toBeNull()
        ->and($parser->parseName('  Searia 1  '))->toBe('Searia 1')
        ->and($parser->parseYear('2019'))->toBe(2019)
        ->and($parser->parseYear('19'))->toBeNull()
        ->and($parser->parseSort('3'))->toBe(3)
        ->and($parser->parseSort('0'))->toBeNull()
        ->and($parser->parseDisplayCode('VI'))->toBe('VI')
        ->and($parser->parseDisplayCode(''))->toBeNull();
});
