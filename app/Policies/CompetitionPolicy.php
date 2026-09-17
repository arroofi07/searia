<?php

namespace App\Policies;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\User;

class CompetitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function view(User $user, Competition $competition): bool
    {
        return $user->managesMasterData();
    }

    public function create(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function update(User $user, Competition $competition): bool
    {
        return $user->managesMasterData();
    }

    public function delete(User $user, Competition $competition): bool
    {
        return $user->managesMasterData() && $competition->isDraft();
    }

    public function duplicate(User $user, Competition $competition): bool
    {
        return $user->managesMasterData();
    }

    public function transition(User $user, Competition $competition): bool
    {
        return $user->managesMasterData();
    }

    public function revert(User $user, Competition $competition): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->managesMasterData()
            && $competition->status === CompetitionStatus::Seeded;
    }

    public function seed(User $user, Competition $competition): bool
    {
        return $user->managesMasterData();
    }
}
