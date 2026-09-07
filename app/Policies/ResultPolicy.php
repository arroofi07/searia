<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Result;
use App\Models\User;

class ResultPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesMasterData() || $user->isJuri();
    }

    public function view(User $user, Result $result): bool
    {
        return $this->accessHeat($user, $result->heatLane?->heat);
    }

    public function create(User $user): bool
    {
        return $user->managesMasterData() || $user->isJuri();
    }

    public function update(User $user, Result $result): bool
    {
        $heat = $result->heatLane?->heat;
        if ($heat === null) {
            return false;
        }

        if ($user->managesMasterData()) {
            return true;
        }

        return $user->isJuri()
            && ! $heat->isResultsLocked()
            && $this->isAssignedToEvent($user, $heat->event_id);
    }

    public function record(User $user, HeatLane $lane): bool
    {
        $heat = $lane->heat;
        if ($heat === null) {
            return false;
        }

        if ($user->managesMasterData()) {
            return true;
        }

        return $user->isJuri()
            && ! $heat->isResultsLocked()
            && $this->isAssignedToEvent($user, $heat->event_id);
    }

    public function lock(User $user, Heat $heat): bool
    {
        if ($user->managesMasterData()) {
            return true;
        }

        return $user->isJuri() && $this->isAssignedToEvent($user, $heat->event_id);
    }

    public function unlock(User $user, Heat $heat): bool
    {
        return $user->managesMasterData();
    }

    public function correct(User $user, Result $result): bool
    {
        return $user->managesMasterData();
    }

    public function viewEvent(User $user, Event $event): bool
    {
        if ($user->managesMasterData()) {
            return true;
        }

        return $user->isJuri() && $this->isAssignedToEvent($user, $event->id);
    }

    private function accessHeat(User $user, ?Heat $heat): bool
    {
        if ($heat === null) {
            return false;
        }

        if ($user->managesMasterData()) {
            return true;
        }

        return $user->isJuri() && $this->isAssignedToEvent($user, $heat->event_id);
    }

    private function isAssignedToEvent(User $user, int $eventId): bool
    {
        return $user->judgedEvents()->whereKey($eventId)->exists();
    }
}
