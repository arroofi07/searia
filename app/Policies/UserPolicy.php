<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function view(User $user, User $model): bool
    {
        return $this->canManage($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function update(User $user, User $model): bool
    {
        return $this->canManage($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $this->canManage($user, $model);
    }

    private function canManage(User $actor, User $target): bool
    {
        if (! $actor->managesMasterData()) {
            return false;
        }

        if ($target->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            return false;
        }

        return true;
    }
}
