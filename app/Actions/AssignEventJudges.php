<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;

class AssignEventJudges
{
    /**
     * @param  list<int>  $userIds
     * @return list<int>
     */
    public function eligibleIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->where('is_active', true)
            ->whereIn('role', [UserRole::Juri, UserRole::Panitia, UserRole::SuperAdmin])
            ->pluck('id')
            ->all();
    }

    /**
     * @param  iterable<Event>  $events
     * @param  list<int>  $userIds
     */
    public function syncMany(iterable $events, array $userIds): int
    {
        $ids = $this->eligibleIds($userIds);
        $count = 0;

        foreach ($events as $event) {
            $event->judges()->sync($ids);
            $count++;
        }

        return $count;
    }
}
