<?php

namespace App\Policies;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\Registration;
use App\Models\User;

class RegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesMasterData() || $user->isPelatih();
    }

    public function view(User $user, Registration $registration): bool
    {
        if ($user->managesMasterData()) {
            return true;
        }

        return $user->isPelatih() && $user->club_id === $registration->athlete?->club_id;
    }

    public function create(User $user, ?Competition $competition = null): bool
    {
        if ($user->managesMasterData()) {
            return true;
        }

        return $user->isPelatih() && $user->club_id !== null;
    }

    public function update(User $user, Registration $registration): bool
    {
        if ($user->managesMasterData()) {
            return true;
        }

        return $this->view($user, $registration)
            && $registration->competition?->status === CompetitionStatus::Registration;
    }

    public function delete(User $user, Registration $registration): bool
    {
        return $this->update($user, $registration);
    }

    public function verify(User $user, Registration $registration): bool
    {
        return $user->managesMasterData();
    }
}
