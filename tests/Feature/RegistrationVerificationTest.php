<?php

use App\Enums\EventGender;
use App\Enums\RegistrationStatus;
use App\Models\Athlete;
use App\Models\Event;
use App\Models\Registration;
use App\Models\RegistrationSubmission;
use App\Models\User;
use App\Notifications\RegistrationStatusChanged;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

it('approves one hundred pending entries in a single request', function () {
    $meet = openRegistrationMeet();
    $ids = [];

    for ($i = 0; $i < 100; $i++) {
        $athlete = Athlete::factory()->create([
            'club_id' => $meet['club']->id,
            'full_name' => 'ATLET BULK '.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
            'gender' => $meet['athlete']->gender,
            'birth_year' => 2016,
        ]);
        $ids[] = Registration::factory()->create([
            'competition_id' => $meet['competition']->id,
            'event_id' => $meet['event']->id,
            'athlete_id' => $athlete->id,
            'age_group_id' => $meet['group']->id,
            'registered_by' => $meet['panitia']->id,
            'status' => RegistrationStatus::Pending,
        ])->id;
    }

    $panitia = \App\Models\User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->post(route('admin.registrations.bulk-approve', $meet['competition']), [
            'registration_ids' => $ids,
        ])
        ->assertRedirect();

    expect(Registration::query()->where('status', RegistrationStatus::Verified)->count())->toBe(100)
        ->and(Registration::query()->whereNotNull('verified_by')->count())->toBe(100)
        ->and(Registration::query()->whereNotNull('verified_at')->count())->toBe(100);
});

it('rejects a rejection that has no reason', function () {
    $meet = openRegistrationMeet();
    $registration = Registration::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_id' => $meet['event']->id,
        'athlete_id' => $meet['athlete']->id,
        'age_group_id' => $meet['group']->id,
        'registered_by' => $meet['panitia']->id,
        'status' => RegistrationStatus::Pending,
    ]);
    $panitia = \App\Models\User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->from(route('admin.registrations.index', $meet['competition']))
        ->patch(route('admin.registrations.reject', $registration), [])
        ->assertRedirect(route('admin.registrations.index', $meet['competition']))
        ->assertSessionHasErrors('rejection_reason');

    expect($registration->fresh()->status)->toBe(RegistrationStatus::Pending);
});

it('sends one status email per submission when approving in bulk', function () {
    Notification::fake();
    $meet = openRegistrationMeet();
    $submission = RegistrationSubmission::factory()->create([
        'competition_id' => $meet['competition']->id,
        'athlete_id' => $meet['athlete']->id,
        'registrant_email' => 'pendaftar@example.test',
    ]);
    $otherEvent = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 15,
        'gender' => EventGender::Male,
    ]);

    $entries = collect([$meet['event'], $otherEvent])->map(fn (Event $event) => Registration::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_id' => $event->id,
        'athlete_id' => $meet['athlete']->id,
        'age_group_id' => $meet['group']->id,
        'submission_id' => $submission->id,
        'registered_by' => null,
        'status' => RegistrationStatus::Pending,
    ]));

    $this->actingAs(User::factory()->panitia()->create())
        ->post(route('admin.registrations.bulk-approve', $meet['competition']), [
            'registration_ids' => $entries->pluck('id')->all(),
        ])
        ->assertRedirect();

    Notification::assertSentOnDemand(
        RegistrationStatusChanged::class,
        fn ($notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'pendaftar@example.test',
    );
    Notification::assertCount(1);
});
