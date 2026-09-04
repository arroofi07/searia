<?php

namespace App\Listeners;

use App\Events\RegistrationsStatusUpdated;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\RegistrationStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendRegistrationStatusNotifications implements ShouldQueue
{
    public function handle(RegistrationsStatusUpdated $event): void
    {
        $registrations = Registration::query()
            ->with(['athlete', 'event', 'competition'])
            ->whereIn('id', $event->registrations->pluck('id'))
            ->get();

        $registrations
            ->groupBy(fn (Registration $registration): int => (int) $registration->athlete->club_id)
            ->each(function ($group) use ($event): void {
                $userId = $group->first()?->registered_by;
                $user = $userId ? User::query()->find($userId) : null;

                if ($user === null) {
                    return;
                }

                $user->notify(new RegistrationStatusChanged($group->values(), $event->status));
            });
    }
}
