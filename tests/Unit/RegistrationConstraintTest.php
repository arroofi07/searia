<?php

use App\Enums\RegistrationStatus;
use App\Models\Athlete;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Registration;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects a second registration for the same athlete and event', function () {
    $athlete = Athlete::factory()->create();
    $event = Event::factory()->create();

    Registration::factory()->create([
        'athlete_id' => $athlete->id,
        'event_id' => $event->id,
        'competition_id' => $event->competition_id,
    ]);

    Registration::factory()->create([
        'athlete_id' => $athlete->id,
        'event_id' => $event->id,
        'competition_id' => $event->competition_id,
    ]);
})->throws(UniqueConstraintViolationException::class);

it('includes verified unpaid-unbilled entries in the seeding scope and excludes unpaid invoices', function () {
    $event = Event::factory()->create();
    $athlete = Athlete::factory()->create();
    $other = Athlete::factory()->create(['club_id' => $athlete->club_id]);

    $eligible = Registration::factory()->create([
        'competition_id' => $event->competition_id,
        'event_id' => $event->id,
        'athlete_id' => $athlete->id,
        'age_group_id' => 1,
        'status' => RegistrationStatus::Verified,
        'invoice_id' => null,
    ]);

    $invoice = Invoice::factory()->create([
        'competition_id' => $event->competition_id,
        'club_id' => $athlete->club_id,
        'status' => \App\Enums\InvoiceStatus::Unpaid,
    ]);

    Registration::factory()->create([
        'competition_id' => $event->competition_id,
        'event_id' => Event::factory()->create(['competition_id' => $event->competition_id])->id,
        'athlete_id' => $other->id,
        'age_group_id' => 1,
        'status' => RegistrationStatus::Verified,
        'invoice_id' => $invoice->id,
    ]);

    $ids = Registration::query()->eligibleForSeeding()->pluck('id');

    expect($ids)->toHaveCount(1)
        ->and($ids->contains($eligible->id))->toBeTrue();
});
