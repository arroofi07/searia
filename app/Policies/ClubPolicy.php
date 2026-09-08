<?php

namespace App\Policies;

use App\Models\Club;
use App\Models\User;

class ClubPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function view(User $user, Club $club): bool
    {
        return $user->managesMasterData();
    }

    public function create(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function update(User $user, Club $club): bool
    {
        return $user->managesMasterData();
    }

    public function delete(User $user, Club $club): bool
    {
        return $user->managesMasterData();
    }

    public function verify(User $user, Club $club): bool
    {
        return $user->managesMasterData();
    }

    public function reject(User $user, Club $club): bool
    {
        return $user->managesMasterData();
    }
}
