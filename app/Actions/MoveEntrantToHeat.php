<?php

namespace App\Actions;

use App\Enums\RegistrationStatus;
use App\Exceptions\CannotAdjustHeatLaneException;
use App\Models\ActivityLog;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MoveEntrantToHeat
{
    public function handle(
        HeatLane $source,
        Heat $targetHeat,
        int $targetLaneNumber,
        User $actor,
        ?string $ipAddress = null,
    ): HeatLane {
        $source->loadMissing(['heat', 'registration']);
        $sourceHeat = $source->heat;

        if ($sourceHeat === null) {
            throw new CannotAdjustHeatLaneException('Lintasan asal tidak ditemukan.');
        }

        if ($sourceHeat->event_id !== $targetHeat->event_id
            || $sourceHeat->age_group_id !== $targetHeat->age_group_id
            || $sourceHeat->round !== $targetHeat->round) {
            throw new CannotAdjustHeatLaneException(
                'Pemindahan lintas nomor lomba atau kelompok umur tidak diizinkan.',
            );
        }

        return DB::transaction(function () use ($source, $targetHeat, $targetLaneNumber, $actor, $ipAddress): HeatLane {
            $occupied = HeatLane::query()
                ->where('heat_id', $targetHeat->id)
                ->where('lane_number', $targetLaneNumber)
                ->lockForUpdate()
                ->first();

            if ($occupied !== null && $occupied->id !== $source->id) {
                throw new CannotAdjustHeatLaneException('Lintasan tujuan sudah terisi.');
            }

            $old = [
                'heat_id' => $source->heat_id,
                'heat_number' => $source->heat?->heat_number,
                'lane_number' => $source->lane_number,
                'registration_id' => $source->registration_id,
            ];

            $source->update([
                'heat_id' => $targetHeat->id,
                'lane_number' => $targetLaneNumber,
            ]);

            $moved = $source->fresh(['heat']);

            ActivityLog::query()->create([
                'user_id' => $actor->id,
                'action' => 'heat_lane.move',
                'subject_type' => HeatLane::class,
                'subject_id' => $moved->id,
                'old_values' => $old,
                'new_values' => [
                    'heat_id' => $moved->heat_id,
                    'heat_number' => $moved->heat?->heat_number,
                    'lane_number' => $moved->lane_number,
                    'registration_id' => $moved->registration_id,
                ],
                'reason' => null,
                'ip_address' => $ipAddress,
            ]);

            return $moved;
        });
    }

    public function withdraw(HeatLane $lane, User $actor, ?string $ipAddress = null): void
    {
        DB::transaction(function () use ($lane, $actor, $ipAddress): void {
            $lane->loadMissing(['registration', 'heat']);

            if ($lane->registration_id === null) {
                throw new CannotAdjustHeatLaneException('Lintasan sudah kosong.');
            }

            $old = [
                'heat_id' => $lane->heat_id,
                'heat_number' => $lane->heat?->heat_number,
                'lane_number' => $lane->lane_number,
                'registration_id' => $lane->registration_id,
            ];

            $lane->registration?->update(['status' => RegistrationStatus::Withdrawn]);

            $lane->update(['registration_id' => null]);

            ActivityLog::query()->create([
                'user_id' => $actor->id,
                'action' => 'heat_lane.withdraw',
                'subject_type' => HeatLane::class,
                'subject_id' => $lane->id,
                'old_values' => $old,
                'new_values' => [
                    'heat_id' => $lane->heat_id,
                    'lane_number' => $lane->lane_number,
                    'registration_id' => null,
                ],
                'reason' => null,
                'ip_address' => $ipAddress,
            ]);
        });
    }
}
