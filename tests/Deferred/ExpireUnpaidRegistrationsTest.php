<?php

use App\Actions\IssueInvoice;
use App\Enums\CompetitionStatus;
use App\Enums\InvoiceStatus;
use App\Enums\RegistrationStatus;
use App\Models\RegistrationSubmission;
use App\Models\User;
use App\Notifications\InvoicePaymentDueSoon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

it('withdraws verified entries when the unpaid invoice is past due', function () {
    $meet = openRegistrationMeet();
    $registration = verifiedRegistration($meet);
    $invoice = app(IssueInvoice::class)->handle($meet['competition'], $meet['club']);
    $invoice->update(['due_at' => now()->subHour(), 'status' => InvoiceStatus::Unpaid]);

    $this->artisan('invoices:expire-unpaid')->assertSuccessful();

    expect($registration->fresh()->status)->toBe(RegistrationStatus::Withdrawn);
});

it('does not expire invoices for competitions that are already seeded', function () {
    $meet = openRegistrationMeet();
    $registration = verifiedRegistration($meet);
    $invoice = app(IssueInvoice::class)->handle($meet['competition'], $meet['club']);
    $invoice->update(['due_at' => now()->subHour(), 'status' => InvoiceStatus::Unpaid]);
    $meet['competition']->update(['status' => CompetitionStatus::Seeded]);

    $this->artisan('invoices:expire-unpaid')->assertSuccessful();

    expect($registration->fresh()->status)->toBe(RegistrationStatus::Verified);
});

it('sends a due reminder once before the deadline', function () {
    Notification::fake();
    Carbon::setTestNow('2026-06-01 12:00:00');

    $meet = openRegistrationMeet();
    $submission = RegistrationSubmission::factory()->create([
        'competition_id' => $meet['competition']->id,
        'athlete_id' => $meet['athlete']->id,
        'registrant_email' => 'pendaftar@example.test',
    ]);
    verifiedRegistration($meet, ['submission_id' => $submission->id]);
    $invoice = app(IssueInvoice::class)->forSubmission($submission);
    $invoice->update([
        'status' => InvoiceStatus::Unpaid,
        'due_at' => now()->addHours(12),
        'reminder_sent_at' => null,
    ]);

    $this->artisan('invoices:expire-unpaid')->assertSuccessful();
    Notification::assertSentOnDemand(
        InvoicePaymentDueSoon::class,
        fn ($notification, $channels, $notifiable): bool => $notifiable->routes['mail'] === 'pendaftar@example.test',
    );
    expect($invoice->fresh()->reminder_sent_at)->not->toBeNull();

    Notification::fake();
    $this->artisan('invoices:expire-unpaid')->assertSuccessful();
    Notification::assertNothingSent();

    Carbon::setTestNow();
});

it('lets panitia restore withdrawn entries for an unpaid invoice', function () {
    $meet = openRegistrationMeet();
    $registration = verifiedRegistration($meet);
    $invoice = app(IssueInvoice::class)->handle($meet['competition'], $meet['club']);
    $registration->update(['status' => RegistrationStatus::Withdrawn]);

    $this->actingAs(User::factory()->panitia()->create())
        ->post(route('admin.invoices.restore', $invoice))
        ->assertRedirect();

    expect($registration->fresh()->status)->toBe(RegistrationStatus::Verified)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid)
        ->and($invoice->fresh()->due_at->greaterThan(now()))->toBeTrue();
});
