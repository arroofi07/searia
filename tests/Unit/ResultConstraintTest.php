<?php

use App\Enums\DisqualificationCode;
use App\Enums\ResultStatus;
use App\Exceptions\InvalidResultException;
use App\Models\HeatLane;
use App\Models\Result;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects dsq status with a time', function () {
    $lane = HeatLane::factory()->create();

    expect(fn () => Result::factory()->create([
        'heat_lane_id' => $lane->id,
        'status' => ResultStatus::Dsq,
        'time_ms' => 45_000,
        'dsq_code' => DisqualificationCode::Sf,
        'recorded_by' => User::factory(),
    ]))->toThrow(InvalidResultException::class);
});

it('rejects ok status without a time', function () {
    $lane = HeatLane::factory()->create();

    expect(fn () => Result::factory()->create([
        'heat_lane_id' => $lane->id,
        'status' => ResultStatus::Ok,
        'time_ms' => null,
        'recorded_by' => User::factory(),
    ]))->toThrow(InvalidResultException::class);
});
