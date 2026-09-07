<?php

namespace App\Notifications;

use App\Models\CertificateArchive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateArchiveReady extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CertificateArchive $archive) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $competition = $this->archive->competition;

        return (new MailMessage)
            ->subject('Arsip sertifikat siap: '.($competition?->name ?? 'SeaRIA'))
            ->line('Arsip sertifikat untuk '.($competition?->name ?? 'kejuaraan').' sudah siap diunduh.')
            ->line('Tautan kedaluwarsa pada '.$this->archive->expires_at?->timezone(config('app.timezone'))->format('d/m/Y H:i').'.')
            ->action('Unduh arsip', route('certificates.archives.download', $this->archive->token));
    }
}
