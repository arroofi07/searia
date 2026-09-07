<?php

use App\Actions\IssueInvoice;
use App\Enums\InvoiceStatus;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Invoice;
use App\Models\User;

it('issues one invoice per club when publishing all at once', function () {
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);

    $clubB = Club::factory()->create();
    $athleteB = Athlete::factory()->create([
        'club_id' => $clubB->id,
        'gender' => $meet['athlete']->gender,
        'birth_year' => 2016,
    ]);
    verifiedRegistration($meet, [
        'athlete_id' => $athleteB->id,
        'registered_by' => User::factory()->pelatih($clubB)->create()->id,
    ]);

    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->post(route('admin.invoices.store-all', $meet['competition']))
        ->assertRedirect(route('admin.invoices.index', $meet['competition']));

    $invoices = Invoice::query()->orderBy('club_id')->get();

    expect($invoices)->toHaveCount(2)
        ->and($invoices->pluck('club_id')->all())->toEqualCanonicalizing([$meet['club']->id, $clubB->id])
        ->and($invoices->pluck('invoice_number')->unique())->toHaveCount(2)
        ->and($invoices->every(fn (Invoice $invoice): bool => (bool) preg_match('/^INV-\d{4}-\d{4}$/', $invoice->invoice_number)))->toBeTrue();

    expect($meet['athlete']->registrations()->first()?->invoice_id)->not->toBeNull();
});

it('rejects reissuing an invoice that is already paid', function () {
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);

    $invoice = app(IssueInvoice::class)->handle($meet['competition'], $meet['club']);
    $invoice->update(['status' => InvoiceStatus::Paid]);

    $this->actingAs(User::factory()->panitia()->create())
        ->post(route('admin.invoices.store', $meet['competition']), [
            'club_id' => $meet['club']->id,
        ])
        ->assertStatus(422);
});

it('downloads an issued invoice as a pdf', function () {
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);
    $invoice = app(IssueInvoice::class)->handle($meet['competition'], $meet['club']);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->get(route('admin.invoices.pdf', $invoice))
        ->assertOk()
        ->assertDownload($invoice->invoice_number.'.pdf');
});
