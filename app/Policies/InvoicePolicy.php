<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->managesMasterData();
    }

    public function create(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function verify(User $user, Invoice $invoice): bool
    {
        return $user->managesMasterData();
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->managesMasterData();
    }
}
