<?php

namespace App\Events;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class RegistrationsStatusUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * @param  Collection<int, Registration>  $registrations
     */
    public function __construct(
        public Collection $registrations,
        public RegistrationStatus $status,
    ) {}
}
