<?php

namespace App\Services;

use App\Enums\CompetitionStatus;
use App\Models\AgeGroup;
use App\Models\Competition;
use Illuminate\Support\Facades\DB;

class CompetitionDuplicator
{
    public function duplicate(Competition $source): Competition
    {
        $source->load(['ageGroups', 'events.ageGroups']);

        return DB::transaction(function () use ($source): Competition {
            $copy = $source->replicate(['slug', 'status']);
            $copy->name = $source->name.' (salinan)';
            $copy->slug = null;
            $copy->status = CompetitionStatus::Draft;
            $copy->save();

            $ageGroupMap = [];

            foreach ($source->ageGroups as $group) {
                $newGroup = $group->replicate();
                $newGroup->competition_id = $copy->id;
                $newGroup->save();
                $ageGroupMap[$group->id] = $newGroup->id;
            }

            foreach ($source->events as $event) {
                $newEvent = $event->replicate();
                $newEvent->competition_id = $copy->id;
                $newEvent->save();

                $eligibleIds = $event->ageGroups
                    ->map(fn (AgeGroup $group): ?int => $ageGroupMap[$group->id] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                if ($eligibleIds !== []) {
                    $newEvent->ageGroups()->attach($eligibleIds);
                }
            }

            return $copy->load(['ageGroups', 'events']);
        });
    }
}
