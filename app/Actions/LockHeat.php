<?php

namespace App\Actions;

use App\Exceptions\CannotLockHeatException;
use App\Models\ActivityLog;
use App\Models\Heat;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LockHeat
{
    public function handle(Heat $heat, User $actor, ?string $ipAddress = null): Heat
    {
        $heat->loadMissing(['lanes.result']);

        if ($heat->isResultsLocked()) {
            return $heat;
        }

        $occupied = $heat->lanes->whereNotNull('registration_id');
        if ($occupied->isEmpty()) {
            throw new CannotLockHeatException('Seri tidak memiliki lintasan terisi.');
        }

        foreach ($occupied as $lane) {
            if ($lane->result === null) {
                throw new CannotLockHeatException('Semua lintasan terisi harus memiliki hasil sebelum dikunci.');
            }
        }

        return DB::transaction(function () use ($heat, $actor, $ipAddress): Heat {
            $heat->lockResults();

            ActivityLog::query()->create([
                'user_id' => $actor->id,
                'action' => 'heat.results_lock',
                'subject_type' => Heat::class,
                'subject_id' => $heat->id,
                'old_values' => ['results_locked_at' => null],
                'new_values' => ['results_locked_at' => $heat->fresh()->results_locked_at?->toIso8601String()],
                'reason' => null,
                'ip_address' => $ipAddress,
            ]);

            return $heat->fresh();
        });
    }
}
