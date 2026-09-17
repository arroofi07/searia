<?php

use App\Enums\CompetitionStatus;
use App\Enums\EventGender;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\ImportBatch;
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

it('keeps seeding list filters on the heat sheet link and back again', function () {
    $meet = openRegistrationMeet();
    $last = null;

    for ($number = 101; $number <= 121; $number++) {
        $event = Event::factory()->create([
            'competition_id' => $meet['competition']->id,
            'event_number' => $number,
            'sort_order' => $number,
        ]);
        $event->ageGroups()->attach($meet['group']->id);
        $last = $event;
    }

    Heat::factory()->create([
        'event_id' => $last->id,
        'age_group_id' => $meet['group']->id,
    ]);

    $panitia = User::factory()->panitia()->create();
    $listUrl = route('admin.seeding.index', [
        $meet['competition'],
        'q' => 'Group 3',
        'page' => 2,
    ]);
    $showUrl = route('admin.seeding.show', [
        $meet['competition'],
        $last,
        $meet['group'],
        'from_q' => 'Group 3',
        'from_page' => 2,
    ]);

    $this->actingAs($panitia)
        ->get($listUrl)
        ->assertOk()
        ->assertSee(e($showUrl), false);

    $this->actingAs($panitia)
        ->get($showUrl)
        ->assertOk()
        ->assertSee(e($listUrl), false);
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

it('paginates import history without selecting the errors json', function () {
    $meet = openRegistrationMeet();
    $panitia = User::factory()->panitia()->create();

    ImportBatch::factory()->count(21)->create([
        'competition_id' => $meet['competition']->id,
        'user_id' => $panitia->id,
        'errors' => ['rows' => array_fill(0, 50, ['payload' => str_repeat('x', 200)])],
    ]);

    expect(ImportBatch::query()->forListing()->toSql())->not->toContain('errors');

    $this->actingAs($panitia)
        ->get(route('admin.imports.index', $meet['competition']))
        ->assertOk()
        ->assertSee('Menampilkan 1–20 dari 21');

    $this->actingAs($panitia)
        ->get(route('admin.imports.index', [$meet['competition'], 'page' => 2]))
        ->assertOk()
        ->assertSee('Menampilkan 21–21 dari 21');
});

it('paginates the public schedule program', function () {
    $competition = Competition::factory()->status(CompetitionStatus::Registration)->create();

    foreach (range(1, 21) as $number) {
        Event::factory()->create([
            'competition_id' => $competition->id,
            'event_number' => $number,
            'sort_order' => $number,
            'distance' => 25 + $number,
            'gender' => EventGender::Male,
        ]);
    }

    $this->get(route('public.competitions.schedule', $competition))
        ->assertOk()
        ->assertSee('Menampilkan 1–20 dari 21');

    $this->get(route('public.competitions.schedule', [$competition, 'page' => 2]))
        ->assertOk()
        ->assertSee('Menampilkan 21–21 dari 21');
});
