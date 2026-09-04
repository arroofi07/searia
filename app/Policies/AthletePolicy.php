<?php

namespace App\Policies;

use App\Models\Athlete;
use App\Models\User;

class AthletePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesMasterData() || $user->isPelatih();
    }

    public function view(User $user, Athlete $athlete): bool
    {
        if ($user->managesMasterData()) {
            return true;
        }

        return $this->ownsClub($user, $athlete);
    }

    public function create(User $user): bool
    {
        return $user->managesMasterData() || $user->isPelatih();
    }

    public function update(User $user, Athlete $athlete): bool
    {
        if ($user->managesMasterData()) {
            return true;
        }

        return $this->ownsClub($user, $athlete);
    }

    public function delete(User $user, Athlete $athlete): bool
    {
        return $this->update($user, $athlete);
    }

    public function manageAny(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function merge(User $user, Athlete $athlete): bool
    {
        return $user->managesMasterData();
    }

    private function ownsClub(User $user, Athlete $athlete): bool
    {
        return $user->isPelatih() && $user->club_id === $athlete->club_id;
    }
}
