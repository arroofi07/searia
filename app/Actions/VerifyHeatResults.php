<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\Heat;
use App\Models\Result;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class VerifyHeatResults
{
    public function handleHeat(Heat $heat, User $actor): int
    {
        return DB::transaction(function () use ($heat, $actor): int {
            $resultIds = Result::query()
                ->whereHas('heatLane', fn ($q) => $q->where('heat_id', $heat->id))
                ->whereNull('verified_at')
                ->pluck('id');

            if ($resultIds->isEmpty()) {
                return 0;
            }

            return Result::query()
                ->whereIn('id', $resultIds)
                ->update([
                    'verified_by' => $actor->id,
                    'verified_at' => now(),
                ]);
        });
    }

    public function handleEvent(Event $event, User $actor): int
    {
        $count = 0;
        foreach ($event->heats as $heat) {
            $count += $this->handleHeat($heat, $actor);
        }

        return $count;
    }
}
