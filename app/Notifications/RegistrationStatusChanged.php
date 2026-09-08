<?php

namespace App\Notifications;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class RegistrationStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, Registration>  $registrations
     */
    public function __construct(
        public Collection $registrations,
        public RegistrationStatus $status,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var Registration $first */
        $first = $this->registrations->first();
        $competition = $first->competition;
        $code = $first->submission?->code;
        $count = $this->registrations->count();

        if ($this->status === RegistrationStatus::Rejected) {
            $message = (new MailMessage)
                ->subject('Pendaftaran ditolak: '.$competition->name)
                ->line($count === 1
                    ? 'Satu entri pendaftaran ditolak.'
                    : $count.' entri pendaftaran ditolak.');

            foreach ($this->registrations as $registration) {
                $message->line(
                    $registration->athlete?->full_name.' · '.$registration->event?->formattedName()
                    .': '.($registration->rejection_reason ?: 'Tanpa alasan'),
                );
            }

            return $message->line(
                'Hubungi panitia dengan menyebutkan kode pendaftaran '.($code ?? '-').' untuk memperbaikinya.',
            );
        }

        return (new MailMessage)
            ->subject('Pendaftaran disetujui: '.$competition->name)
            ->line($count === 1
                ? 'Satu entri pendaftaran disetujui panitia.'
                : $count.' entri pendaftaran Anda disetujui panitia.')
            ->line('Kode pendaftaran: '.($code ?? '-'));
    }
}
