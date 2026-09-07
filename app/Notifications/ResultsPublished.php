<?php

namespace App\Notifications;

use App\Models\Competition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResultsPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Competition $competition) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Hasil terbit: '.$this->competition->name)
            ->line('Hasil kejuaraan '.$this->competition->name.' telah dipublikasikan.')
            ->action('Lihat hasil', route('results.index', $this->competition));
    }
}
