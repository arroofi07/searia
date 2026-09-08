<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoicePaymentDueSoon extends Notification implements ShouldQueue
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
        $due = $invoice->due_at?->translatedFormat('d M Y H:i') ?? 'segera';

        return (new MailMessage)
            ->subject('Pengingat pembayaran: '.$invoice->invoice_number)
            ->line('Tagihan '.$invoice->invoice_number.' untuk '.$invoice->competition?->name.' jatuh tempo pada '.$due.'.')
            ->line('Nominal: Rp '.number_format($invoice->amount, 0, ',', '.'))
            ->line('Kirim bukti transfer ke panitia dengan menyebutkan kode pendaftaran '
                .($invoice->submission?->code ?? $invoice->invoice_number).'.');
    }
}
