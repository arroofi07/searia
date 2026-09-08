<?php

use App\Models\Competition;
use App\Models\Invoice;
use App\Models\RegistrationSubmission;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects a second invoice for the same submission', function () {
    $competition = Competition::factory()->create();
    $submission = RegistrationSubmission::factory()->create(['competition_id' => $competition->id]);

    Invoice::factory()->create([
        'competition_id' => $competition->id,
        'club_id' => null,
        'submission_id' => $submission->id,
    ]);

    Invoice::factory()->create([
        'competition_id' => $competition->id,
        'club_id' => null,
        'submission_id' => $submission->id,
    ]);
})->throws(UniqueConstraintViolationException::class);
