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
                ->line('Atlet Anda kini memenuhi syarat untuk masuk pembagian seri dan lintasan.');
        }

        return (new MailMessage)
            ->subject('Tagihan dibuka kembali: '.$invoice->invoice_number)
            ->line('Tagihan '.$invoice->invoice_number.' untuk '.$competition.' dikembalikan ke status belum lunas.')
            ->line('Alasan: '.($invoice->rejection_reason ?: 'Tidak dicantumkan'))
            ->line('Hubungi panitia dengan menyebutkan kode pendaftaran '
                .($invoice->submission?->code ?? $invoice->invoice_number).'.');
    }
}
