<?php

use App\Models\Club;
use App\Models\Competition;
use App\Models\Invoice;
use App\Services\Invoice\InvoiceNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('issues human-readable invoice numbers sequenced per competition year', function () {
    $competition = Competition::factory()->create(['start_date' => '2026-06-01']);
    $clubA = Club::factory()->create();
    $clubB = Club::factory()->create();
    $generator = new InvoiceNumberGenerator;

    $first = DB::transaction(fn (): string => $generator->next($competition));
    Invoice::factory()->create([
        'competition_id' => $competition->id,
        'club_id' => $clubA->id,
        'invoice_number' => $first,
    ]);

    $second = DB::transaction(fn (): string => $generator->next($competition));
    Invoice::factory()->create([
        'competition_id' => $competition->id,
        'club_id' => $clubB->id,
        'invoice_number' => $second,
    ]);

    expect($first)->toBe('INV-2026-0001')
        ->and($second)->toBe('INV-2026-0002');
});

it('restarts the sequence for a different year', function () {
    $nextYear = Competition::factory()->create(['start_date' => '2027-01-15']);
    $generator = new InvoiceNumberGenerator;

    $number = DB::transaction(fn (): string => $generator->next($nextYear));

    expect($number)->toBe('INV-2027-0001');
});
