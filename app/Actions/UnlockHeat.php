<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\Heat;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UnlockHeat
{
    public function handle(Heat $heat, User $actor, string $reason, ?string $ipAddress = null): Heat
    {
        if (! $heat->isResultsLocked()) {
            return $heat;
        }

        return DB::transaction(function () use ($heat, $actor, $reason, $ipAddress): Heat {
            $old = $heat->results_locked_at?->toIso8601String();
            $heat->unlockResults();

            ActivityLog::query()->create([
                'user_id' => $actor->id,
                'action' => 'heat.results_unlock',
                'subject_type' => Heat::class,
                'subject_id' => $heat->id,
                'old_values' => ['results_locked_at' => $old],
                'new_values' => ['results_locked_at' => null],
                'reason' => $reason,
                'ip_address' => $ipAddress,
            ]);

            return $heat->fresh();
        });
    }
}
