<?php

namespace App\Policies;

use App\Models\Athlete;
use App\Models\User;

class AthletePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function view(User $user, Athlete $athlete): bool
    {
        return $user->managesMasterData();
    }

    public function create(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function update(User $user, Athlete $athlete): bool
    {
        return $user->managesMasterData();
    }

    public function delete(User $user, Athlete $athlete): bool
    {
        return $user->managesMasterData();
    }

    public function manageAny(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function merge(User $user, Athlete $athlete): bool
    {
        return $user->managesMasterData();
    }
}
