<?php

namespace App\Policies;

use App\Models\Competition;
use App\Models\Registration;
use App\Models\User;

/**
 * Pendaftaran mandiri oleh peserta berjalan lewat rute publik tanpa akun, sehingga
 * kebijakan ini hanya mengatur apa yang boleh dilakukan panitia atas entri yang masuk.
 */
class RegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function view(User $user, Registration $registration): bool
    {
        return $user->managesMasterData();
    }

    public function create(User $user, ?Competition $competition = null): bool
    {
        return $user->managesMasterData();
    }

    public function update(User $user, Registration $registration): bool
    {
        return $user->managesMasterData();
    }

    public function delete(User $user, Registration $registration): bool
    {
        return $user->managesMasterData();
    }

    public function verify(User $user, Registration $registration): bool
    {
        return $user->managesMasterData();
    }
}
