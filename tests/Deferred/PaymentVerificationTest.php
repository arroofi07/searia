<?php

use App\Actions\IssueInvoice;
use App\Enums\InvoiceStatus;
use App\Models\Registration;
use App\Models\RegistrationSubmission;
use App\Models\User;
use App\Notifications\InvoiceVerificationResult;
use Illuminate\Support\Facades\Notification;

it('includes club entries in the seeding scope after the invoice is marked paid', function () {
    $meet = openRegistrationMeet();
    $registration = verifiedRegistration($meet);
    $invoice = app(IssueInvoice::class)->handle($meet['competition'], $meet['club']);

    $this->actingAs(User::factory()->panitia()->create())
        ->post(route('admin.invoices.approve', $invoice))
        ->assertRedirect();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and(Registration::query()->eligibleForSeeding()->whereKey($registration->id)->exists())->toBeTrue();
});

it('emails the registrant when their submission invoice is marked paid', function () {
    Notification::fake();
    $meet = openRegistrationMeet();
    $submission = RegistrationSubmission::factory()->create([
        'competition_id' => $meet['competition']->id,
        'athlete_id' => $meet['athlete']->id,
        'registrant_email' => 'pendaftar@example.test',
    ]);
    verifiedRegistration($meet, ['submission_id' => $submission->id]);

    $invoice = app(IssueInvoice::class)->forSubmission($submission);

    $this->actingAs(User::factory()->panitia()->create())
        ->post(route('admin.invoices.approve', $invoice))
        ->assertRedirect();

    Notification::assertSentOnDemand(
        InvoiceVerificationResult::class,
        fn ($notification, $channels, $notifiable): bool => $notifiable->routes['mail'] === 'pendaftar@example.test',
    );
});

it('rejects reopening an invoice without a reason', function () {
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);
    $invoice = app(IssueInvoice::class)->handle($meet['competition'], $meet['club']);
    $invoice->update(['status' => InvoiceStatus::Paid]);

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.invoices.show', $invoice))
        ->post(route('admin.invoices.reject', $invoice->fresh()), [])
        ->assertRedirect(route('admin.invoices.show', $invoice))
        ->assertSessionHasErrors('rejection_reason');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

it('returns a paid invoice to unpaid when panitia reopens it with a reason', function () {
    Notification::fake();
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);
    $invoice = app(IssueInvoice::class)->handle($meet['competition'], $meet['club']);
    $invoice->update(['status' => InvoiceStatus::Paid]);

    $this->actingAs(User::factory()->panitia()->create())
        ->post(route('admin.invoices.reject', $invoice->fresh()), [
            'rejection_reason' => 'Nominal transfer tidak sesuai.',
        ])
        ->assertRedirect();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid)
        ->and($invoice->fresh()->rejection_reason)->toBe('Nominal transfer tidak sesuai.');
});
