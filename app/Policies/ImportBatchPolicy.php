<?php

namespace App\Policies;

use App\Models\ImportBatch;
use App\Models\User;

class ImportBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function view(User $user, ImportBatch $batch): bool
    {
        return $user->managesMasterData();
    }

    public function create(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function update(User $user, ImportBatch $batch): bool
    {
        return $user->managesMasterData();
    }

    public function delete(User $user, ImportBatch $batch): bool
    {
        return $user->managesMasterData();
    }
}
