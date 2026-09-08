<?php

use App\Enums\RegistrationStatus;
use App\Models\ActivityLog;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('covers the access matrix for key capabilities', function () {
    $super = User::factory()->superAdmin()->create();
    $panitia = User::factory()->panitia()->create();
    $judge = User::factory()->juri()->create();

    $meet = openRegistrationMeet();

    $cases = [
        ['user' => $panitia, 'route' => 'admin.competitions.index', 'status' => 200],
        ['user' => $judge, 'route' => 'admin.competitions.index', 'status' => 403],
        ['user' => $panitia, 'route' => 'admin.activity-logs.index', 'status' => 200],
        ['user' => $judge, 'route' => 'admin.activity-logs.index', 'status' => 403],
        ['user' => $panitia, 'route' => 'admin.exports.index', 'args' => [$meet['competition']], 'status' => 200],
        ['user' => $judge, 'route' => 'admin.exports.index', 'args' => [$meet['competition']], 'status' => 403],
        ['user' => $super, 'route' => 'admin.competitions.index', 'status' => 200],
        ['user' => $judge, 'route' => 'judge.tasks', 'status' => 200],
        ['user' => $panitia, 'route' => 'judge.tasks', 'status' => 200],
    ];

    foreach ($cases as $case) {
        $args = $case['args'] ?? [];
        $this->actingAs($case['user'])
            ->get(route($case['route'], $args))
            ->assertStatus($case['status']);
    }

    auth()->logout();
    $this->get(route('admin.competitions.index'))->assertRedirect(route('login'));
});

it('lets panitia create a manual registration that is eligible for seeding', function () {
    $meet = openRegistrationMeet();
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->post(route('admin.registrations.store', $meet['competition']), [
            'athlete_id' => $meet['athlete']->id,
            'event_id' => $meet['event']->id,
            'seed_time' => '00:45.00',
            'verify_now' => '1',
        ])
        ->assertRedirect(route('admin.registrations.index', $meet['competition']));

    $registration = Registration::query()->first();

    expect($registration)->not->toBeNull()
        ->and($registration->status)->toBe(RegistrationStatus::Verified)
        ->and(Registration::query()->eligibleForSeeding()->whereKey($registration->id)->exists())->toBeTrue();
});

it('filters activity logs by user action and date range', function () {
    $admin = User::factory()->panitia()->create();
    $other = User::factory()->panitia()->create();
    $meet = openRegistrationMeet();

    ActivityLog::record($admin, 'result.correct', $meet['competition'], ['a' => 1], ['a' => 2], 'satu');
    ActivityLog::record($other, 'heat.results_lock', $meet['competition'], null, ['locked' => true], null);
    ActivityLog::query()->where('action', 'heat.results_lock')->update([
        'created_at' => now()->subDays(10),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.activity-logs.index', [
            'user_id' => $admin->id,
            'action' => 'result.correct',
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertSee('result.correct')
        ->assertSee($admin->name);

    expect(ActivityLog::query()->where('action', 'result.correct')->where('user_id', $admin->id)->count())->toBe(1);
});

it('opens subject history from the activity log subject route', function () {
    $admin = User::factory()->panitia()->create();
    $meet = openRegistrationMeet();
    ActivityLog::record($admin, 'competition.update', $meet['competition'], ['name' => 'A'], ['name' => 'B']);

    $this->actingAs($admin)
        ->get('/admin/activity-logs/for-subject?'.http_build_query([
            'type' => \App\Models\Competition::class,
            'id' => $meet['competition']->id,
        ]))
        ->assertOk()
        ->assertSee('competition.update');
});
