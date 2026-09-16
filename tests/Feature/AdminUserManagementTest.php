<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('allows panitia to create a juri account', function () {
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->post(route('admin.users.store'), [
            'name' => 'Juri Baru',
            'email' => 'juri.baru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::Juri->value,
            'phone' => '08123456789',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('status');

    $created = User::query()->where('email', 'juri.baru@example.com')->first();

    expect($created)->not->toBeNull()
        ->and($created->role)->toBe(UserRole::Juri)
        ->and($created->is_active)->toBeTrue()
        ->and(Hash::check('password123', $created->password))->toBeTrue();
});

it('allows panitia to create a panitia account', function () {
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->post(route('admin.users.store'), [
            'name' => 'Panitia Baru',
            'email' => 'panitia.baru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::Panitia->value,
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.users.index'));

    expect(User::query()->where('email', 'panitia.baru@example.com')->first()?->role)
        ->toBe(UserRole::Panitia);
});

it('rejects creating a super admin account from the form', function () {
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->from(route('admin.users.create'))
        ->post(route('admin.users.store'), [
            'name' => 'Admin Baru',
            'email' => 'admin.baru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::SuperAdmin->value,
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.users.create'))
        ->assertSessionHasErrors('role');

    expect(User::query()->where('email', 'admin.baru@example.com')->exists())->toBeFalse();
});

it('forbids juri from managing users', function () {
    $juri = User::factory()->juri()->create();

    $this->actingAs($juri)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('forbids panitia from editing a super admin', function () {
    $panitia = User::factory()->panitia()->create();
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($panitia)
        ->get(route('admin.users.edit', $admin))
        ->assertForbidden();
});

it('deactivates a user instead of deleting', function () {
    $panitia = User::factory()->panitia()->create();
    $juri = User::factory()->juri()->create(['is_active' => true]);

    $this->actingAs($panitia)
        ->delete(route('admin.users.destroy', $juri))
        ->assertRedirect(route('admin.users.index'));

    expect($juri->fresh()->is_active)->toBeFalse()
        ->and(User::query()->find($juri->id))->not->toBeNull();
});

it('lists users filtered by role and search', function () {
    User::factory()->juri()->create(['name' => 'Andi Juri', 'email' => 'andi@example.com']);
    User::factory()->panitia()->create(['name' => 'Budi Panitia', 'email' => 'budi@example.com']);

    $actor = User::factory()->panitia()->create();

    $this->actingAs($actor)
        ->get(route('admin.users.index', [
            'search' => 'andi',
            'role' => UserRole::Juri->value,
        ]))
        ->assertOk()
        ->assertSee('Andi Juri')
        ->assertDontSee('Budi Panitia');
});
