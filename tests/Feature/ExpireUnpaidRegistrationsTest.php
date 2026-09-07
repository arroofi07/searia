<?php

use App\Actions\IssueInvoice;
use App\Enums\CompetitionStatus;
use App\Enums\InvoiceStatus;
use App\Enums\RegistrationStatus;
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
    verifiedRegistration($meet);
    $invoice = app(IssueInvoice::class)->handle($meet['competition'], $meet['club']);
    $invoice->update([
        'status' => InvoiceStatus::Unpaid,
        'due_at' => now()->addHours(12),
        'reminder_sent_at' => null,
    ]);

    $this->artisan('invoices:expire-unpaid')->assertSuccessful();
    Notification::assertSentTo($meet['coach'], InvoicePaymentDueSoon::class);
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
