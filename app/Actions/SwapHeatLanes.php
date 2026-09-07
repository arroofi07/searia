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
        if ($left->heat_id !== $right->heat_id) {
            throw new CannotAdjustHeatLaneException('Penukaran hanya boleh dalam seri yang sama.');
        }

        if ($left->id === $right->id) {
            throw new CannotAdjustHeatLaneException('Tidak dapat menukar lintasan dengan dirinya sendiri.');
        }

        DB::transaction(function () use ($left, $right, $actor, $ipAddress): void {
            $left->loadMissing('registration.athlete');
            $right->loadMissing('registration.athlete');

            $oldLeft = $left->registration_id;
            $oldRight = $right->registration_id;

            $left->update(['registration_id' => null]);
            $right->update(['registration_id' => $oldLeft]);
            $left->update(['registration_id' => $oldRight]);

            ActivityLog::query()->create([
                'user_id' => $actor->id,
                'action' => 'heat_lane.swap',
                'subject_type' => HeatLane::class,
                'subject_id' => $left->id,
                'old_values' => [
                    'lane_number' => $left->lane_number,
                    'registration_id' => $oldLeft,
                    'swapped_with_lane' => $right->lane_number,
                    'swapped_with_registration_id' => $oldRight,
                ],
                'new_values' => [
                    'lane_number' => $left->lane_number,
                    'registration_id' => $oldRight,
                    'swapped_with_lane' => $right->lane_number,
                    'swapped_with_registration_id' => $oldLeft,
                ],
                'reason' => null,
                'ip_address' => $ipAddress,
            ]);

            ActivityLog::query()->create([
                'user_id' => $actor->id,
                'action' => 'heat_lane.swap',
                'subject_type' => HeatLane::class,
                'subject_id' => $right->id,
                'old_values' => [
                    'lane_number' => $right->lane_number,
                    'registration_id' => $oldRight,
                    'swapped_with_lane' => $left->lane_number,
                    'swapped_with_registration_id' => $oldLeft,
                ],
                'new_values' => [
                    'lane_number' => $right->lane_number,
                    'registration_id' => $oldLeft,
                    'swapped_with_lane' => $left->lane_number,
                    'swapped_with_registration_id' => $oldRight,
                ],
                'reason' => null,
                'ip_address' => $ipAddress,
            ]);
        });
    }
}
