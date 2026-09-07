<?php

use App\Enums\ClubStatus;
use App\Enums\InvoiceStatus;
use App\Models\ActivityLog;
use App\Models\Club;
use App\Models\Invoice;
use App\Models\Result;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('covers the access matrix for key capabilities', function () {
    $super = User::factory()->superAdmin()->create();
    $panitia = User::factory()->panitia()->create();
    $coach = User::factory()->pelatih()->create();
    $judge = User::factory()->juri()->create();

    $meet = openRegistrationMeet();

    $cases = [
        ['user' => $panitia, 'route' => 'admin.competitions.index', 'status' => 200],
        ['user' => $coach, 'route' => 'admin.competitions.index', 'status' => 403],
        ['user' => $judge, 'route' => 'admin.competitions.index', 'status' => 403],
        ['user' => $panitia, 'route' => 'admin.activity-logs.index', 'status' => 200],
        ['user' => $coach, 'route' => 'admin.activity-logs.index', 'status' => 403],
        ['user' => $judge, 'route' => 'admin.activity-logs.index', 'status' => 403],
        ['user' => $panitia, 'route' => 'admin.exports.index', 'args' => [$meet['competition']], 'status' => 200],
        ['user' => $coach, 'route' => 'admin.exports.index', 'args' => [$meet['competition']], 'status' => 403],
        ['user' => $super, 'route' => 'admin.competitions.index', 'status' => 200],
        ['user' => $judge, 'route' => 'judge.tasks', 'status' => 200],
        ['user' => $coach, 'route' => 'judge.tasks', 'status' => 403],
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

it('rejects coach access to another club invoice by direct URL', function () {
    $meet = openRegistrationMeet();
    $clubA = $meet['club'];
    $clubB = Club::factory()->create(['status' => ClubStatus::Verified]);
    $coachA = User::factory()->pelatih($clubA)->create();
    $coachB = User::factory()->pelatih($clubB)->create();

    $invoiceA = Invoice::factory()->create([
        'competition_id' => $meet['competition']->id,
        'club_id' => $clubA->id,
        'status' => InvoiceStatus::Unpaid,
        'proof_path' => 'invoices/1/proof.jpg',
    ]);

    $this->actingAs($coachB)
        ->get(route('coach.invoices.show', $invoiceA))
        ->assertForbidden();

    $this->actingAs($coachA)
        ->get(route('coach.invoices.show', $invoiceA))
        ->assertOk();
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
            'type' => Competition::class,
            'id' => $meet['competition']->id,
        ]))
        ->assertOk()
        ->assertSee('competition.update');
});
