<?php

use App\Models\Club;
use App\Models\Competition;
use App\Models\Invoice;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects a second invoice for the same club and competition', function () {
    $competition = Competition::factory()->create();
    $club = Club::factory()->create();

    Invoice::factory()->create([
        'competition_id' => $competition->id,
        'club_id' => $club->id,
    ]);

    Invoice::factory()->create([
        'competition_id' => $competition->id,
        'club_id' => $club->id,
    ]);
})->throws(UniqueConstraintViolationException::class);
