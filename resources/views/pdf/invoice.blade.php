<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #64748b; margin: 0 0 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #f8fafc; }
        .right { text-align: right; }
        .totals { margin-top: 16px; }
        .bank { margin-top: 24px; padding: 12px; border: 1px solid #cbd5e1; }
    </style>
</head>
<body>
    <h1>Tagihan {{ $invoice->invoice_number }}</h1>
    <p class="muted">{{ $invoice->competition->name }} · {{ $invoice->club->name }}</p>
    <p>Status: {{ $invoice->status->label() }}</p>
    <p>Batas pembayaran: {{ $invoice->due_at?->translatedFormat('d M Y H:i') ?? '—' }}</p>

    <table>
        <thead>
            <tr>
                <th>Atlet</th>
                <th>Nomor lomba</th>
                <th class="right">Biaya</th>
                <th class="right">Denda</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines() as $line)
                <tr>
                    <td>{{ $line['athlete_name'] }}</td>
                    <td>{{ $line['event_name'] }}</td>
                    <td class="right">Rp {{ number_format($line['base_fee'], 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($line['late_fee'], 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($line['subtotal'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="totals"><strong>Total: Rp {{ number_format($invoice->amount, 0, ',', '.') }}</strong> ({{ $invoice->item_count }} entri)</p>

    @if (config('searia.invoice.bank_account'))
        <div class="bank">
            <p><strong>Transfer ke</strong></p>
            <p>{{ config('searia.invoice.bank_name') }}</p>
            <p>{{ config('searia.invoice.bank_account') }} a.n. {{ config('searia.invoice.bank_holder') }}</p>
        </div>
    @endif
</body>
</html>
