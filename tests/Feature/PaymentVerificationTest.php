<?php

use App\Actions\IssueInvoice;
use App\Enums\InvoiceStatus;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\InvoiceVerificationResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

it('includes club entries in the seeding scope after the invoice is marked paid', function () {
    Storage::fake('local');
    Notification::fake();
    $meet = openRegistrationMeet();
    $registration = verifiedRegistration($meet);
    $invoice = app(IssueInvoice::class)->handle($meet['competition'], $meet['club']);

    $this->actingAs($meet['coach'])->post(route('coach.invoices.proof.store', $invoice), [
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ]);

    $this->actingAs(User::factory()->panitia()->create())
        ->post(route('admin.invoices.approve', $invoice->fresh()))
        ->assertRedirect();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and(Registration::query()->eligibleForSeeding()->whereKey($registration->id)->exists())->toBeTrue();

    Notification::assertSentTo($meet['coach'], InvoiceVerificationResult::class);
});

it('rejects a payment rejection that has no reason', function () {
    Storage::fake('local');
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);
    $invoice = app(IssueInvoice::class)->handle($meet['competition'], $meet['club']);

    $this->actingAs($meet['coach'])->post(route('coach.invoices.proof.store', $invoice), [
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ]);

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.invoices.show', $invoice))
        ->post(route('admin.invoices.reject', $invoice->fresh()), [])
        ->assertRedirect(route('admin.invoices.show', $invoice))
        ->assertSessionHasErrors('rejection_reason');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::WaitingVerification);
});

it('returns the invoice to unpaid when proof is rejected with a reason', function () {
    Storage::fake('local');
    Notification::fake();
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);
    $invoice = app(IssueInvoice::class)->handle($meet['competition'], $meet['club']);

    $this->actingAs($meet['coach'])->post(route('coach.invoices.proof.store', $invoice), [
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ]);

    $this->actingAs(User::factory()->panitia()->create())
        ->post(route('admin.invoices.reject', $invoice->fresh()), [
            'rejection_reason' => 'Nominal transfer tidak sesuai.',
        ])
        ->assertRedirect();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid)
        ->and($invoice->fresh()->rejection_reason)->toBe('Nominal transfer tidak sesuai.');

    Notification::assertSentTo($meet['coach'], InvoiceVerificationResult::class);
});
