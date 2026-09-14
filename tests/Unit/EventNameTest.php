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
        'event_number' => 35,
        'distance' => 50,
        'stroke' => Stroke::Freestyle,
        'equipment' => Equipment::Fins,
        'gender' => EventGender::Male,
    ]);

    expect($event->programName())->toBe('50 M BEBAS (FINS)');
});

it('defines eighteen official program pairs numbered 1 through 36', function () {
    $program = Event::defaultProgram();

    expect($program)->toHaveCount(18)
        ->and($program[0]['male_number'])->toBe(1)
        ->and($program[0]['female_number'])->toBe(2)
        ->and($program[0]['eligible_codes'])->toBe(['2', '3', '4', '5'])
        ->and($program[6]['male_number'])->toBe(13)
        ->and($program[6]['stroke'])->toBe(Stroke::Breaststroke)
        ->and($program[10]['male_number'])->toBe(21)
        ->and($program[10]['distance'])->toBe(200)
        ->and($program[10]['eligible_codes'])->toBe(['1'])
        ->and($program[17]['male_number'])->toBe(35)
        ->and($program[17]['equipment'])->toBe(Equipment::Fins)
        ->and($program[17]['stroke'])->toBe(Stroke::Freestyle)
        ->and($program[17]['eligible_codes'])->toBe(['2', '3', '4', '5', '6', '7']);
});

it('assigns eligible groups from the official Fun Swimming matrix', function () {
    expect(Event::defaultEligibleGroupCodes(50, Stroke::Butterfly, Equipment::None))->toBe(['2', '3', '4', '5'])
        ->and(Event::defaultEligibleGroupCodes(200, Stroke::Freestyle, Equipment::None))->toBe(['1'])
        ->and(Event::defaultEligibleGroupCodes(25, Stroke::Breaststroke, Equipment::None))->toBe(['5', '6', '7', '8', '9'])
        ->and(Event::defaultEligibleGroupCodes(25, Stroke::Butterfly, Equipment::Kickboard))->toBe(['6', '7'])
        ->and(Event::defaultEligibleGroupCodes(25, Stroke::Breaststroke, Equipment::Kickboard))->toBe(['6', '7', '8', '9'])
        ->and(Event::defaultEligibleGroupCodes(50, Stroke::Freestyle, Equipment::Kickboard))->toBe(['4', '5']);
});
