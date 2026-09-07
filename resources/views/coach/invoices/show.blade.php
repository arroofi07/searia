@php
    use App\Enums\InvoiceStatus;
@endphp

@extends('layouts.app')

@section('title', $invoice->invoice_number)

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ $invoice->invoice_number }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $competition->name }} · {{ $invoice->club->name }}</p>
        </div>
        <a href="{{ route('coach.registrations.index', $competition) }}" class="text-sm text-teal-800 hover:underline">Kembali ke ringkasan entri</a>
    </div>

    <dl class="mt-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-4 text-sm">
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Status</dt>
            <dd class="mt-1 text-lg font-semibold">{{ $invoice->status->label() }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Nominal</dt>
            <dd class="mt-1 text-lg font-semibold">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Jumlah entri</dt>
            <dd class="mt-1 text-lg font-semibold">{{ $invoice->item_count }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Batas bayar</dt>
            <dd class="mt-1 text-lg font-semibold">{{ $invoice->due_at?->translatedFormat('d M Y H:i') ?? '—' }}</dd>
        </div>
    </dl>

    @if (config('searia.invoice.bank_account'))
        <div class="mt-4 rounded-md border border-slate-200 bg-white px-4 py-3 text-sm">
            <p class="font-medium">Transfer ke</p>
            <p>{{ config('searia.invoice.bank_name') }} {{ config('searia.invoice.bank_account') }} a.n. {{ config('searia.invoice.bank_holder') }}</p>
        </div>
    @endif

    @if ($invoice->rejection_reason && $invoice->status !== InvoiceStatus::Paid)
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            Bukti ditolak: {{ $invoice->rejection_reason }}
        </div>
    @endif

    <div class="mt-8 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-2 font-medium">Atlet</th>
                    <th class="px-4 py-2 font-medium">Nomor</th>
                    <th class="px-4 py-2 font-medium">Biaya</th>
                    <th class="px-4 py-2 font-medium">Denda</th>
                    <th class="px-4 py-2 font-medium">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->lines() as $line)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-2">{{ $line['athlete_name'] }}</td>
                        <td class="px-4 py-2">{{ $line['event_name'] }}</td>
                        <td class="px-4 py-2">Rp {{ number_format($line['base_fee'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2">Rp {{ number_format($line['late_fee'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2">Rp {{ number_format($line['subtotal'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($invoice->canUploadProof())
        <form method="POST" action="{{ route('coach.invoices.proof.store', $invoice) }}" enctype="multipart/form-data" class="mt-8 max-w-xl rounded-lg border border-slate-200 bg-white p-5">
            @csrf
            <label for="proof" class="block text-sm font-medium text-slate-700">Unggah bukti transfer (JPG, PNG, atau PDF, maks. 5 MB)</label>
            <input id="proof" type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" required class="mt-2 block w-full text-sm">
            @error('proof') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <button class="mt-4 rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">
                {{ $invoice->proof_path ? 'Ganti bukti' : 'Unggah bukti' }}
            </button>
        </form>
    @endif

    @if ($invoice->proof_path)
        <p class="mt-4 text-sm">
            <a href="{{ route('invoices.proof', $invoice) }}" class="text-teal-800 hover:underline" target="_blank" rel="noopener">Lihat bukti yang diunggah</a>
        </p>
    @endif
@endsection
