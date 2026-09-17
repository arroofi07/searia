<?php

use App\Models\AgeGroup;
use App\Models\Competition;
use App\Models\User;

function ageGroupPayload(AgeGroup $group, array $overrides = []): array
{
    return [
        'editing_id' => $group->id,
        'code' => $group->code,
        'name' => $group->name,
        'display_code' => $group->display_code,
        'birth_year_start' => $group->birth_year_start,
        'birth_year_end' => $group->birth_year_end,
        'sort_order' => $group->sort_order,
        ...$overrides,
    ];
}

it('renders age groups as editable fields', function () {
    $meet = openRegistrationMeet();

    $this->actingAs(User::factory()->panitia()->create())
        ->get(route('admin.competitions.age-groups.index', $meet['competition']))
        ->assertOk()
        ->assertSee('name="name"', false)
        ->assertSee('value="Group 3"', false)
        ->assertSee('Simpan');
});

it('updates an age group name and print label', function () {
    $competition = Competition::factory()->create();
    $group = AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '3',
        'name' => 'Group 3',
        'display_code' => 'III',
        'birth_year_start' => 2015,
        'birth_year_end' => 2016,
        'sort_order' => 3,
    ]);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->from(route('admin.competitions.age-groups.index', $competition))
        ->put(route('admin.competitions.age-groups.update', [$competition, $group]), ageGroupPayload($group, [
            'name' => 'Searia 3',
            'display_code' => 'S3',
        ]))
        ->assertRedirect(route('admin.competitions.age-groups.index', $competition))
        ->assertSessionHas('status', 'Kelompok umur diperbarui.');

    expect($group->fresh())
        ->name->toBe('Searia 3')
        ->display_code->toBe('S3')
        ->birth_year_start->toBe(2015)
        ->birth_year_end->toBe(2016);
});

it('updates birth years when the group has no registrations', function () {
    $competition = Competition::factory()->create();
    $group = AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '4',
        'birth_year_start' => 2014,
        'birth_year_end' => 2014,
        'sort_order' => 4,
    ]);

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.competitions.age-groups.index', $competition))
        ->put(route('admin.competitions.age-groups.update', [$competition, $group]), ageGroupPayload($group, [
            'birth_year_start' => 2013,
            'birth_year_end' => 2014,
        ]))
        ->assertRedirect(route('admin.competitions.age-groups.index', $competition));

    expect($group->fresh())
        ->birth_year_start->toBe(2013)
        ->birth_year_end->toBe(2014);
});

it('rejects changing birth years after the group has registrations', function () {
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.competitions.age-groups.index', $meet['competition']))
        ->put(route('admin.competitions.age-groups.update', [$meet['competition'], $meet['group']]), ageGroupPayload($meet['group'], [
            'birth_year_start' => 2014,
            'birth_year_end' => 2014,
        ]))
        ->assertRedirect(route('admin.competitions.age-groups.index', $meet['competition']))
        ->assertSessionHasErrors('birth_year_end');

    expect($meet['group']->fresh())
        ->birth_year_start->toBe(2015)
        ->birth_year_end->toBe(2016);
});

it('still allows renaming a group that already has registrations', function () {
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->get(route('admin.competitions.age-groups.index', $meet['competition']))
        ->assertOk()
        ->assertSee('Terkunci karena sudah ada pendaftaran');

    $this->actingAs($panitia)
        ->from(route('admin.competitions.age-groups.index', $meet['competition']))
        ->put(route('admin.competitions.age-groups.update', [$meet['competition'], $meet['group']]), ageGroupPayload($meet['group'], [
            'name' => 'Searia 3',
        ]))
        ->assertRedirect()
        ->assertSessionHas('status', 'Kelompok umur diperbarui.');

    expect($meet['group']->fresh()->name)->toBe('Searia 3');
});
