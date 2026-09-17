<?php

namespace App\Services\Invoice;

use App\Models\Invoice;
use App\Support\PdfRenderer;
use Illuminate\Http\Response;

class InvoicePdf
{
    public function download(Invoice $invoice): Response
    {
        $invoice->loadMissing(['competition', 'club', 'registrations.athlete', 'registrations.event']);

        $filename = $invoice->invoice_number.'.pdf';

        return PdfRenderer::download('pdf.invoice', [
            'invoice' => $invoice,
        ], $filename);
    }

    public function render(Invoice $invoice): string
    {
        $invoice->loadMissing(['competition', 'club', 'registrations.athlete', 'registrations.event']);

        return PdfRenderer::output('pdf.invoice', [
            'invoice' => $invoice,
        ]);
    }
}
