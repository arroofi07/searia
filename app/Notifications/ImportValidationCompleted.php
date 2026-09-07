<?php

namespace App\Notifications;

use App\Models\ImportBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportValidationCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ImportBatch $batch) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $batch = $this->batch;
        $competition = $batch->competition;

        return (new MailMessage)
            ->subject('Validasi import selesai: '.$competition?->name)
            ->line('Berkas '.$batch->original_filename.' sudah divalidasi.')
            ->line('Dibaca '.$batch->total_rows.' baris, valid '.$batch->valid_rows.', bermasalah '.$batch->invalid_rows.'.')
            ->action('Lihat pratinjau', route('admin.imports.show', $batch));
    }
}
