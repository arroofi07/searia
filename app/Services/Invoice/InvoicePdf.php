<?php

namespace App\Services\Invoice;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoicePdf
{
    public function download(Invoice $invoice): Response
    {
        $invoice->loadMissing(['competition', 'club', 'registrations.athlete', 'registrations.event']);

        $filename = $invoice->invoice_number.'.pdf';

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
        ])->download($filename);
    }

    public function render(Invoice $invoice): string
    {
        $invoice->loadMissing(['competition', 'club', 'registrations.athlete', 'registrations.event']);

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
        ])->output();
    }
}
