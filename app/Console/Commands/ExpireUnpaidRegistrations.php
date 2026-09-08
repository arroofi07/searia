<?php

namespace App\Console\Commands;

use App\Enums\CompetitionStatus;
use App\Enums\InvoiceStatus;
use App\Enums\RegistrationStatus;
use App\Models\Invoice;
use App\Notifications\InvoicePaymentDueSoon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class ExpireUnpaidRegistrations extends Command
{
    protected $signature = 'invoices:expire-unpaid';

    protected $description = 'Kirim pengingat jatuh tempo dan batalkan entri pada tagihan yang lewat batas waktu';

    public function handle(): int
    {
        $reminded = $this->sendReminders();
        $expired = $this->expireOverdue();

        $this->info($reminded.' pengingat dikirim, '.$expired.' tagihan kedaluwarsa.');

        return self::SUCCESS;
    }

    private function sendReminders(): int
    {
        $hours = (int) config('searia.invoice.reminder_hours', 24);
        $count = 0;

        Invoice::query()
            ->where('status', InvoiceStatus::Unpaid)
            ->whereNull('reminder_sent_at')
            ->whereNotNull('due_at')
            ->where('due_at', '>', now())
            ->where('due_at', '<=', now()->addHours($hours))
            ->with(['submission', 'competition'])
            ->each(function (Invoice $invoice) use (&$count): void {
                $email = $invoice->submission?->registrant_email;

                if ($email !== null) {
                    Notification::route('mail', $email)->notify(new InvoicePaymentDueSoon($invoice));
                }

                $invoice->update(['reminder_sent_at' => now()]);
                $count++;
            });

        return $count;
    }

    private function expireOverdue(): int
    {
        $count = 0;

        Invoice::query()
            ->where('status', InvoiceStatus::Unpaid)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereHas('competition', function ($query): void {
                $query->whereNotIn('status', [
                    CompetitionStatus::Seeded,
                    CompetitionStatus::Running,
                    CompetitionStatus::Finished,
                    CompetitionStatus::Published,
                ]);
            })
            ->with('registrations')
            ->each(function (Invoice $invoice) use (&$count): void {
                $invoice->registrations()
                    ->where('status', RegistrationStatus::Verified)
                    ->update(['status' => RegistrationStatus::Withdrawn]);
                $count++;
            });

        return $count;
    }
}
