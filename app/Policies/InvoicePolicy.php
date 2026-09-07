<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesMasterData() || $user->isPelatih();
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->managesMasterData()) {
            return true;
        }

        return $user->isPelatih() && $user->club_id === $invoice->club_id;
    }

    public function create(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function uploadProof(User $user, Invoice $invoice): bool
    {
        return $user->isPelatih()
            && $user->club_id === $invoice->club_id
            && $invoice->canUploadProof();
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
