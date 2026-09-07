<?php

use App\Actions\IssueInvoice;
use App\Enums\InvoiceStatus;
use App\Models\Club;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('marks an invoice waiting for verification after the coach uploads proof', function () {
    Storage::fake('local');
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);
    $invoice = app(IssueInvoice::class)->handle($meet['competition'], $meet['club']);

    $this->actingAs($meet['coach'])
        ->post(route('coach.invoices.proof.store', $invoice), [
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])
        ->assertRedirect();

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::WaitingVerification)
        ->and($invoice->proof_path)->not->toBeNull();

    Storage::disk('local')->assertExists($invoice->proof_path);
    expect(Storage::disk('public')->exists($invoice->proof_path))->toBeFalse();
});

it('allows replacing the proof while the invoice is not paid', function () {
    Storage::fake('local');
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);
    $invoice = app(IssueInvoice::class)->handle($meet['competition'], $meet['club']);

    $this->actingAs($meet['coach'])->post(route('coach.invoices.proof.store', $invoice), [
        'proof' => UploadedFile::fake()->image('bukti-1.png'),
    ]);
    $firstPath = $invoice->fresh()->proof_path;

    $this->actingAs($meet['coach'])->post(route('coach.invoices.proof.store', $invoice), [
        'proof' => UploadedFile::fake()->create('bukti-2.pdf', 120, 'application/pdf'),
    ]);

    $invoice->refresh();

    expect($invoice->proof_path)->not->toBe($firstPath)
        ->and($invoice->status)->toBe(InvoiceStatus::WaitingVerification);

    Storage::disk('local')->assertMissing($firstPath);
    Storage::disk('local')->assertExists($invoice->proof_path);
});

it('forbids a coach of another club from accessing payment proof', function () {
    Storage::fake('local');
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);
    $invoice = app(IssueInvoice::class)->handle($meet['competition'], $meet['club']);

    $this->actingAs($meet['coach'])->post(route('coach.invoices.proof.store', $invoice), [
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ]);

    $otherClub = Club::factory()->create();
    $otherCoach = User::factory()->pelatih($otherClub)->create();

    $this->actingAs($otherCoach)
        ->get(route('invoices.proof', $invoice))
        ->assertForbidden();

    $this->actingAs($otherCoach)
        ->get(route('coach.invoices.show', $invoice))
        ->assertForbidden();
});
