@php
    use App\Enums\InvoiceStatus;
@endphp

@extends('layouts.app')

@section('title', 'Tagihan')

@section('content')
    <h1 class="text-2xl font-semibold">Tagihan</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }}</p>

    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'invoices'])

    @error('club_id')
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('admin.invoices.store-all', $competition) }}" class="mt-6 flex flex-wrap items-end gap-3">
        @csrf
        <div>
            <label for="due_at_all" class="block text-sm font-medium text-slate-700">Batas waktu pembayaran</label>
            <input id="due_at_all" type="datetime-local" name="due_at"
                value="{{ old('due_at', now()->addDays(config('searia.invoice.due_days', 7))->format('Y-m-d\TH:i')) }}"
                class="mt-1 rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Terbitkan semua klub</button>
    </form>

    <div class="mt-8 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Klub</th>
                    <th class="px-4 py-3 font-medium">Entri terverifikasi</th>
                    <th class="px-4 py-3 font-medium">Tagihan</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Nominal</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clubs as $club)
                    @php
                        $invoice = $invoices->get($club->id);
                    @endphp
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">{{ $club->name }}</td>
                        <td class="px-4 py-3">{{ $verifiedByClub[$club->id] ?? 0 }}</td>
                        <td class="px-4 py-3">{{ $invoice?->invoice_number ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $invoice?->status->label() ?? 'Belum diterbitkan' }}</td>
                        <td class="px-4 py-3">
                            @if ($invoice)
                                Rp {{ number_format($invoice->amount, 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($invoice)
                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-teal-800 hover:underline">Rincian</a>
                            @endif
                            @if (! $invoice || $invoice->status !== InvoiceStatus::Paid)
                                <form method="POST" action="{{ route('admin.invoices.store', $competition) }}" class="mt-2 inline">
                                    @csrf
                                    <input type="hidden" name="club_id" value="{{ $club->id }}">
                                    <button class="text-teal-800 hover:underline">{{ $invoice ? 'Terbitkan ulang' : 'Terbitkan' }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada entri terverifikasi untuk ditagih.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
