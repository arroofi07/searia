<?php

use App\Models\Club;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('throws a unique index violation when two clubs share a name', function () {
    Club::factory()->create(['name' => 'SeaRIA Aquatic Padang']);

    Club::factory()->create(['name' => 'SeaRIA Aquatic Padang']);
})->throws(UniqueConstraintViolationException::class);
