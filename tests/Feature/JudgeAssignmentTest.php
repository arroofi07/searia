<?php

use App\Enums\EventGender;
use App\Enums\Stroke;
use App\Models\Event;
use App\Models\User;
use App\Support\ListPaginator;

it('explains judge assignment in plain Indonesian', function () {
    $meet = openRegistrationMeet();
    $judge = User::factory()->juri()->create(['name' => 'Juri Kolam 1']);

    $this->actingAs(User::factory()->panitia()->create())
        ->get(route('admin.judges.edit', $meet['competition']))
        ->assertOk()
        ->assertSee('Penugasan juri')
        ->assertSee('Tentukan siapa yang mencatat hasil')
        ->assertSee('Isi cepat')
        ->assertSee('Belum ditugaskan')
        ->assertSee('Juri Kolam 1')
        ->assertSee('Juri')
        ->assertDontSee('(juri)');
});

it('lets panitia filter unassigned events', function () {
    $meet = openRegistrationMeet();
    $judge = User::factory()->juri()->create();
    $assigned = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 14,
        'sort_order' => 14,
        'gender' => EventGender::Female,
        'stroke' => Stroke::Freestyle,
    ]);
    $assigned->judges()->attach($judge->id);

    $this->actingAs(User::factory()->panitia()->create())
        ->get(route('admin.judges.edit', [$meet['competition'], 'status' => 'unassigned']))
        ->assertOk()
        ->assertSee($meet['event']->formattedName())
        ->assertDontSee($assigned->formattedName());
});

it('saves judges for one event without clearing the others', function () {
    $meet = openRegistrationMeet();
    $first = User::factory()->juri()->create(['name' => 'Andi Juri']);
    $second = User::factory()->juri()->create(['name' => 'Budi Juri']);
    $other = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 14,
        'sort_order' => 14,
    ]);
    $other->judges()->attach($first->id);

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.judges.edit', $meet['competition']))
        ->put(route('admin.judges.update', $meet['competition']), [
            'intent' => 'event',
            'event_id' => $meet['event']->id,
            'judge_ids' => [$second->id],
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($meet['event']->fresh()->judges->pluck('id')->all())->toBe([$second->id])
        ->and($other->fresh()->judges->pluck('id')->all())->toBe([$first->id]);
});

it('fills only empty events when asked to fill empty', function () {
    $meet = openRegistrationMeet();
    $andi = User::factory()->juri()->create();
    $budi = User::factory()->juri()->create();
    $kept = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 14,
        'sort_order' => 14,
    ]);
    $kept->judges()->attach($andi->id);

    $this->actingAs(User::factory()->panitia()->create())
        ->put(route('admin.judges.update', $meet['competition']), [
            'intent' => 'fill_empty',
            'judge_ids' => [$budi->id],
        ])
        ->assertRedirect();

    expect($meet['event']->fresh()->judges->pluck('id')->all())->toBe([$budi->id])
        ->and($kept->fresh()->judges->pluck('id')->all())->toBe([$andi->id]);
});

it('copies selected judges onto empty events in the same session', function () {
    $meet = openRegistrationMeet();
    $judge = User::factory()->juri()->create();
    $sameSession = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 14,
        'session' => 1,
        'sort_order' => 14,
    ]);
    $otherSession = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 21,
        'session' => 2,
        'sort_order' => 21,
    ]);

    $this->actingAs(User::factory()->panitia()->create())
        ->put(route('admin.judges.update', $meet['competition']), [
            'intent' => 'fill_session',
            'event_id' => $meet['event']->id,
            'judge_ids' => [$judge->id],
        ])
        ->assertRedirect();

    expect($meet['event']->fresh()->judges->pluck('id')->all())->toBe([$judge->id])
        ->and($sameSession->fresh()->judges->pluck('id')->all())->toBe([$judge->id])
        ->and($otherSession->fresh()->judges->pluck('id')->all())->toBe([]);
});

it('replaces judges on every event matching the filter', function () {
    $meet = openRegistrationMeet();
    $andi = User::factory()->juri()->create();
    $budi = User::factory()->juri()->create();
    $sessionTwo = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 21,
        'session' => 2,
        'sort_order' => 21,
    ]);
    $meet['event']->judges()->attach($andi->id);
    $sessionTwo->judges()->attach($andi->id);

    $this->actingAs(User::factory()->panitia()->create())
        ->put(route('admin.judges.update', $meet['competition']), [
            'intent' => 'replace_filtered',
            'session' => '1',
            'judge_ids' => [$budi->id],
        ])
        ->assertRedirect();

    expect($meet['event']->fresh()->judges->pluck('id')->all())->toBe([$budi->id])
        ->and($sessionTwo->fresh()->judges->pluck('id')->all())->toBe([$andi->id]);
});

it('paginates the assignment list', function () {
    $meet = openRegistrationMeet();

    foreach (range(14, 14 + ListPaginator::PER_PAGE - 1) as $number) {
        Event::factory()->create([
            'competition_id' => $meet['competition']->id,
            'event_number' => $number,
            'sort_order' => $number,
        ]);
    }

    $this->actingAs(User::factory()->panitia()->create())
        ->get(route('admin.judges.edit', $meet['competition']))
        ->assertOk()
        ->assertSee('Menampilkan 1–20 dari 21');
});

it('rejects bulk assign without a judge', function () {
    $meet = openRegistrationMeet();

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.judges.edit', $meet['competition']))
        ->put(route('admin.judges.update', $meet['competition']), [
            'intent' => 'fill_empty',
            'judge_ids' => [],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('judge_ids');
});

it('forbids a judge from opening the assignment screen', function () {
    $meet = openRegistrationMeet();

    $this->actingAs(User::factory()->juri()->create())
        ->get(route('admin.judges.edit', $meet['competition']))
        ->assertForbidden();
});
