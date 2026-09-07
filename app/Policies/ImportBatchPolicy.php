<?php

namespace App\Policies;

use App\Models\ImportBatch;
use App\Models\User;

class ImportBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesMasterData() || $user->isPelatih();
    }

    public function view(User $user, ImportBatch $batch): bool
    {
        if ($user->managesMasterData()) {
            return true;
        }

        return $user->isPelatih() && $user->id === $batch->user_id;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, ImportBatch $batch): bool
    {
        return $this->view($user, $batch);
    }

    public function delete(User $user, ImportBatch $batch): bool
    {
        return $user->managesMasterData() || $user->id === $batch->user_id;
    }
}
