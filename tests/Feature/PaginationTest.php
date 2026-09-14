<?php

use App\Models\Event;
use App\Models\User;
use App\Support\ListPaginator;

it('paginates seeding pairs and keeps later combinations on the next page', function () {
    $meet = openRegistrationMeet();

    for ($number = 101; $number <= 121; $number++) {
        $event = Event::factory()->create([
            'competition_id' => $meet['competition']->id,
            'event_number' => $number,
            'sort_order' => $number,
        ]);
        $event->ageGroups()->attach($meet['group']->id);
    }

    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->get(route('admin.seeding.index', $meet['competition']))
        ->assertOk()
        ->assertSee('Menampilkan 1–20 dari 22');

    $this->actingAs($panitia)
        ->get(route('admin.seeding.index', [$meet['competition'], 'page' => 2]))
        ->assertOk()
        ->assertSee('Menampilkan 21–22 dari 22');
});

it('paginates the event management table', function () {
    $competition = \App\Models\Competition::factory()->create();
    $panitia = User::factory()->panitia()->create();

    foreach (range(1, 21) as $number) {
        Event::factory()->create([
            'competition_id' => $competition->id,
            'event_number' => $number,
            'sort_order' => $number,
        ]);
    }

    $this->actingAs($panitia)
        ->get(route('admin.competitions.events.index', $competition))
        ->assertOk()
        ->assertSee('Menampilkan 1–20 dari 21');

    $this->actingAs($panitia)
        ->get(route('admin.competitions.events.index', [$competition, 'page' => 2]))
        ->assertOk()
        ->assertSee('Menampilkan 21–21 dari 21');
});

it('paginates age groups', function () {
    $competition = \App\Models\Competition::factory()->create();
    $panitia = User::factory()->panitia()->create();

    foreach (range(1, 21) as $order) {
        $competition->ageGroups()->create([
            'code' => (string) $order,
            'name' => 'Group '.$order,
            'display_code' => (string) $order,
            'birth_year_start' => 2000 + $order,
            'birth_year_end' => 2000 + $order,
            'sort_order' => $order,
        ]);
    }

    $this->actingAs($panitia)
        ->get(route('admin.competitions.age-groups.index', $competition))
        ->assertOk()
        ->assertSee('Menampilkan 1–20 dari 21')
        ->assertDontSee('Group 21');

    $this->actingAs($panitia)
        ->get(route('admin.competitions.age-groups.index', [$competition, 'page' => 2]))
        ->assertOk()
        ->assertSee('Group 21');
});

it('slices a collection with ListPaginator', function () {
    $page = ListPaginator::for(range(1, 25));

    expect($page->total())->toBe(25)
        ->and($page->count())->toBe(20)
        ->and($page->firstItem())->toBe(1)
        ->and($page->lastItem())->toBe(20);
});
