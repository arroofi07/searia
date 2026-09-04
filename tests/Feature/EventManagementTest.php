<?php

use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\Stroke;
use App\Models\Competition;
use App\Models\Event;
use App\Models\User;

it('rejects two events that share an event number in the same competition', function () {
    $competition = Competition::factory()->create();
    Event::factory()->create([
        'competition_id' => $competition->id,
        'event_number' => 13,
    ]);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->from(route('admin.competitions.events.index', $competition))
        ->post(route('admin.competitions.events.store', $competition), [
            'event_number' => 13,
            'gender' => EventGender::Female->value,
            'distance' => 50,
            'stroke' => Stroke::Breaststroke->value,
            'equipment' => Equipment::None->value,
            'session' => 1,
        ])
        ->assertRedirect(route('admin.competitions.events.index', $competition))
        ->assertSessionHasErrors('event_number');

    expect($competition->events()->count())->toBe(1);
});
