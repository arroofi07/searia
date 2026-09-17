<?php

namespace App\Actions;

use App\Exceptions\CannotAdjustHeatLaneException;
use App\Models\ActivityLog;
use App\Models\HeatLane;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SwapHeatLanes
{
    public function handle(HeatLane $left, HeatLane $right, User $actor, ?string $ipAddress = null): void
    {
        $left->loadMissing('heat');
        $right->loadMissing('heat');

        if ($left->heat === null || $right->heat === null) {
            throw new CannotAdjustHeatLaneException('Lintasan tidak ditemukan.');
        }

        if ($left->heat->event_id !== $right->heat->event_id
            || $left->heat->age_group_id !== $right->heat->age_group_id
            || $left->heat->round !== $right->heat->round) {
            throw new CannotAdjustHeatLaneException(
                'Penukaran hanya boleh dalam nomor lomba dan kelompok umur yang sama.',
            );
        }

        if ($left->id === $right->id) {
            throw new CannotAdjustHeatLaneException('Tidak dapat menukar lintasan dengan dirinya sendiri.');
        }

        if ($left->registration_id === null || $right->registration_id === null) {
            throw new CannotAdjustHeatLaneException('Kedua lintasan harus terisi untuk ditukar.');
        }

        DB::transaction(function () use ($left, $right, $actor, $ipAddress): void {
            $ordered = collect([$left, $right])->sortBy('id')->values();
            HeatLane::query()->whereIn('id', $ordered->pluck('id'))->lockForUpdate()->get();

            $left->refresh()->loadMissing(['heat', 'registration.athlete']);
            $right->refresh()->loadMissing(['heat', 'registration.athlete']);

            if ($left->registration_id === null || $right->registration_id === null) {
                throw new CannotAdjustHeatLaneException('Kedua lintasan harus terisi untuk ditukar.');
            }

            $oldLeft = $left->registration_id;
            $oldRight = $right->registration_id;

            $left->update(['registration_id' => null]);
            $right->update(['registration_id' => $oldLeft]);
            $left->update(['registration_id' => $oldRight]);

            $this->audit($actor, $left, $right, $oldLeft, $oldRight, $ipAddress);
            $this->audit($actor, $right, $left, $oldRight, $oldLeft, $ipAddress);
        });
    }

    private function audit(
        User $actor,
        HeatLane $lane,
        HeatLane $other,
        ?int $oldRegistrationId,
        ?int $otherOldRegistrationId,
        ?string $ipAddress,
    ): void {
        ActivityLog::query()->create([
            'user_id' => $actor->id,
            'action' => 'heat_lane.swap',
            'subject_type' => HeatLane::class,
            'subject_id' => $lane->id,
            'old_values' => [
                'heat_id' => $lane->heat_id,
                'heat_number' => $lane->heat?->heat_number,
                'lane_number' => $lane->lane_number,
                'registration_id' => $oldRegistrationId,
                'swapped_with_heat_id' => $other->heat_id,
                'swapped_with_heat_number' => $other->heat?->heat_number,
                'swapped_with_lane' => $other->lane_number,
                'swapped_with_registration_id' => $otherOldRegistrationId,
            ],
            'new_values' => [
                'heat_id' => $lane->heat_id,
                'heat_number' => $lane->heat?->heat_number,
                'lane_number' => $lane->lane_number,
                'registration_id' => $otherOldRegistrationId,
                'swapped_with_heat_id' => $other->heat_id,
                'swapped_with_heat_number' => $other->heat?->heat_number,
                'swapped_with_lane' => $other->lane_number,
                'swapped_with_registration_id' => $oldRegistrationId,
            ],
            'reason' => null,
            'ip_address' => $ipAddress,
        ]);
    }
}
