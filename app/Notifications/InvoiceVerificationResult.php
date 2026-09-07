<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceVerificationResult extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invoice = $this->invoice;
        $competition = $invoice->competition?->name ?? 'kejuaraan';

        if ($invoice->isPaid()) {
            return (new MailMessage)
                ->subject('Pembayaran diverifikasi: '.$invoice->invoice_number)
                ->line('Tagihan '.$invoice->invoice_number.' untuk '.$competition.' telah ditandai lunas.')
                ->action('Lihat tagihan', route('coach.invoices.show', $invoice));
        }

        return (new MailMessage)
            ->subject('Pembayaran ditolak: '.$invoice->invoice_number)
            ->line('Bukti pembayaran tagihan '.$invoice->invoice_number.' untuk '.$competition.' ditolak.')
            ->line('Alasan: '.(string) $invoice->rejection_reason)
            ->action('Unggah ulang bukti', route('coach.invoices.show', $invoice));
    }
}
