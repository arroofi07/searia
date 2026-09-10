<?php

use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\Stroke;
use App\Models\Event;

it('formats an event name from distance stroke equipment and gender', function () {
    $event = new Event([
        'event_number' => 13,
        'distance' => 50,
        'stroke' => Stroke::Breaststroke,
        'equipment' => Equipment::None,
        'gender' => EventGender::Male,
    ]);

    expect($event->formattedName())->toBe('50 M Gaya Dada - Putra');
});

it('prints the official program name for fins freestyle', function () {
    $event = new Event([
        'event_number' => 33,
        'distance' => 50,
        'stroke' => Stroke::Freestyle,
        'equipment' => Equipment::Fins,
        'gender' => EventGender::Male,
    ]);

    expect($event->programName())->toBe('50 M BEBAS (FINS)');
});

it('defines seventeen official program pairs numbered 1 through 34', function () {
    $program = Event::defaultProgram();

    expect($program)->toHaveCount(17)
        ->and($program[0]['male_number'])->toBe(1)
        ->and($program[0]['female_number'])->toBe(2)
        ->and($program[6]['male_number'])->toBe(13)
        ->and($program[6]['stroke'])->toBe(Stroke::Breaststroke)
        ->and($program[16]['male_number'])->toBe(33)
        ->and($program[16]['equipment'])->toBe(Equipment::Fins)
        ->and($program[16]['stroke'])->toBe(Stroke::Freestyle);
});
