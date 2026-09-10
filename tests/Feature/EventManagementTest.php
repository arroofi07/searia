<?php

use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\Stroke;
use App\Models\AgeGroup;
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

it('fills the official 34-event program in PA/PI order', function () {
    $competition = Competition::factory()->create();
    foreach (AgeGroup::defaultDefinitions($competition->year()) as $definition) {
        $competition->ageGroups()->create($definition);
    }
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->post(route('admin.competitions.events.quick-fill', $competition))
        ->assertRedirect();

    $events = $competition->events()->orderBy('event_number')->get();
    $dadaPutra = $events->firstWhere('event_number', 13);

    expect($events)->toHaveCount(34)
        ->and($events->first()->event_number)->toBe(1)
        ->and($events->first()->programName())->toBe('50 M GAYA KUPU-KUPU')
        ->and($dadaPutra->stroke)->toBe(Stroke::Breaststroke)
        ->and($dadaPutra->ageGroups()->pluck('code')->all())->toEqualCanonicalizing(['1', '2', '3', '4'])
        ->and($events->firstWhere('event_number', 33)->programName())->toBe('50 M BEBAS (FINS)')
        ->and($events->last()->event_number)->toBe(34);

    $this->actingAs($panitia)
        ->get(route('admin.competitions.events.index', $competition))
        ->assertOk()
        ->assertSee('Susunan Acara Perlombaan')
        ->assertSee('50 M GAYA KUPU-KUPU')
        ->assertSee('50 M BEBAS (FINS)');
});
