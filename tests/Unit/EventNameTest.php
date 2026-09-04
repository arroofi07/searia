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
