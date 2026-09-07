<?php

namespace App\Http\Controllers\Coach;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentProofRequest;
use App\Models\Competition;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentProofController extends Controller
{
    public function index(Competition $competition): View
    {
        $this->authorize('viewAny', Invoice::class);

        $clubId = request()->user()?->club_id;
        abort_unless($clubId, 403);

        $invoice = Invoice::query()
            ->where('competition_id', $competition->id)
            ->where('club_id', $clubId)
            ->first();

        if ($invoice === null) {
            return view('coach.invoices.missing', ['competition' => $competition]);
        }

        $this->authorize('view', $invoice);

        return view('coach.invoices.show', [
            'competition' => $competition,
            'invoice' => $invoice,
        ]);
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['club', 'competition']);

        return view('coach.invoices.show', [
            'competition' => $invoice->competition,
            'invoice' => $invoice,
        ]);
    }

    public function store(StorePaymentProofRequest $request, Invoice $invoice): RedirectResponse
    {
        $file = $request->file('proof');
        $path = \App\Support\UploadedFileGuard::storePrivate(
            $file,
            'invoices/'.$invoice->id,
            ['image/jpeg', 'image/png', 'application/pdf'],
            (int) config('searia.invoice.proof_max_bytes', 5 * 1024 * 1024),
        );

        if ($invoice->proof_path) {
            Storage::disk('local')->delete($invoice->proof_path);
        }

        $invoice->update([
            'proof_path' => $path,
            'status' => InvoiceStatus::WaitingVerification,
            'rejection_reason' => null,
        ]);

        return back()->with('status', 'Bukti pembayaran diunggah.');
    }

    public function file(Invoice $invoice): StreamedResponse
    {
        return app(\App\Http\Controllers\SecureFileController::class)->invoiceProof($invoice);
    }
}
