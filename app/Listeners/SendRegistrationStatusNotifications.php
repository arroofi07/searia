<?php

namespace App\Listeners;

use App\Events\RegistrationsStatusUpdated;
use App\Models\Registration;
use App\Notifications\RegistrationStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * Pendaftar tidak punya akun, jadi pemberitahuan dikirim ke alamat email yang ia
 * cantumkan saat mengisi form. Satu email ringkasan per pengiriman, bukan per entri.
 */
class SendRegistrationStatusNotifications implements ShouldQueue
{
    public function handle(RegistrationsStatusUpdated $event): void
    {
        Registration::query()
            ->with(['athlete', 'event', 'competition', 'submission'])
            ->whereIn('id', $event->registrations->pluck('id'))
            ->whereNotNull('submission_id')
            ->get()
            ->filter(fn (Registration $registration): bool => filled($registration->submission?->registrant_email))
            ->groupBy('submission_id')
            ->each(function ($group) use ($event): void {
                /** @var Registration $first */
                $first = $group->first();

                Notification::route('mail', $first->submission->registrant_email)
                    ->notify(new RegistrationStatusChanged($group->values(), $event->status));
            });
    }
}
