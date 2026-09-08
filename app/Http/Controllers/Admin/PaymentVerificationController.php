<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectInvoiceRequest;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Notifications\InvoiceVerificationResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Response;

class PaymentVerificationController extends Controller
{
    public function approve(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('verify', $invoice);
        abort_if($invoice->isPaid(), Response::HTTP_UNPROCESSABLE_ENTITY);

        $old = ['status' => $invoice->status->value];

        $invoice->update([
            'status' => InvoiceStatus::Paid,
            'verified_by' => $request->user()?->id,
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        ActivityLog::record(
            $request->user(),
            'invoice.verify',
            $invoice,
            $old,
            ['status' => InvoiceStatus::Paid->value],
            'Verifikasi pembayaran disetujui',
            $request->ip(),
        );

        $this->notifyRegistrant($invoice->fresh(['submission', 'competition']));

        return back()->with('status', 'Tagihan ditandai lunas.');
    }

    public function reject(RejectInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('verify', $invoice);
        abort_unless($invoice->isPaid(), Response::HTTP_UNPROCESSABLE_ENTITY);

        $old = ['status' => $invoice->status->value];

        $invoice->update([
            'status' => InvoiceStatus::Unpaid,
            'rejection_reason' => $request->validated('rejection_reason'),
            'verified_by' => $request->user()?->id,
            'verified_at' => now(),
        ]);

        ActivityLog::record(
            $request->user(),
            'invoice.reject',
            $invoice,
            $old,
            [
                'status' => InvoiceStatus::Unpaid->value,
                'rejection_reason' => $request->validated('rejection_reason'),
            ],
            'Bukti pembayaran ditolak',
            $request->ip(),
        );

        $this->notifyRegistrant($invoice->fresh(['submission', 'competition']));

        return back()->with('status', 'Tagihan dikembalikan ke status belum lunas.');
    }

    public function restore(Invoice $invoice): RedirectResponse
    {
        $this->authorize('restore', $invoice);
        abort_if($invoice->competition?->status->isSeededOrLater(), 403, 'Kejuaraan sudah dikunci seeding.');

        DB::transaction(function () use ($invoice): void {
            $invoice->registrations()
                ->where('status', RegistrationStatus::Withdrawn)
                ->update(['status' => RegistrationStatus::Verified]);

            $invoice->update([
                'status' => InvoiceStatus::Unpaid,
                'due_at' => now()->addDays((int) config('searia.invoice.due_days', 7)),
                'reminder_sent_at' => null,
            ]);
        });

        return back()->with('status', 'Entri dikembalikan dan tagihan dibuka kembali.');
    }

    private function notifyRegistrant(Invoice $invoice): void
    {
        $email = $invoice->submission?->registrant_email;

        if ($email === null) {
            return;
        }

        Notification::route('mail', $email)->notify(new InvoiceVerificationResult($invoice));
    }
}
