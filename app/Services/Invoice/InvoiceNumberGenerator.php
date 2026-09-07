<?php

namespace App\Services\Invoice;

use App\Models\Competition;
use App\Models\Invoice;

class InvoiceNumberGenerator
{
    public function next(Competition $competition): string
    {
        $prefix = 'INV-'.$competition->year().'-';

        $latest = Invoice::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = 1;

        if (is_string($latest) && str_starts_with($latest, $prefix)) {
            $sequence = ((int) substr($latest, strlen($prefix))) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
