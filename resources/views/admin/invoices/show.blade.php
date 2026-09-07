@php
    use App\Enums\InvoiceStatus;
    use App\Enums\RegistrationStatus;
@endphp

@extends('layouts.app')

@section('title', $invoice->invoice_number)

@section('content')
    <h1 class="text-2xl font-semibold">{{ $invoice->invoice_number }}</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }} · {{ $invoice->club->name }}</p>

    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'invoices'])

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

    @if ($invoice->rejection_reason && $invoice->status !== InvoiceStatus::Paid)
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            Ditolak: {{ $invoice->rejection_reason }}
        </div>
    @endif

    <div class="mt-4 flex flex-wrap gap-3">
        <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Unduh PDF</a>
        @if ($invoice->status !== InvoiceStatus::Paid)
            <form method="POST" action="{{ route('admin.invoices.store', $competition) }}">
                @csrf
                <input type="hidden" name="club_id" value="{{ $invoice->club_id }}">
                <button class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Terbitkan ulang</button>
            </form>
        @endif
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <h2 class="border-b border-slate-100 px-4 py-3 font-medium">Rincian entri</h2>
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
                    @forelse ($invoice->lines() as $line)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-2">{{ $line['athlete_name'] }}</td>
                            <td class="px-4 py-2">{{ $line['event_name'] }}</td>
                            <td class="px-4 py-2">Rp {{ number_format($line['base_fee'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2">
                                @if ($line['is_late'])
                                    Rp {{ number_format($line['late_fee'], 0, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2">Rp {{ number_format($line['subtotal'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">Tidak ada rincian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="font-medium">Bukti pembayaran</h2>
            @if ($invoice->proof_path)
                <p class="mt-2 text-sm text-slate-500">Nominal yang harus dicocokkan: Rp {{ number_format($invoice->amount, 0, ',', '.') }}</p>
                <a href="{{ route('invoices.proof', $invoice) }}" class="mt-3 inline-block text-sm text-teal-800 hover:underline" target="_blank" rel="noopener">Buka berkas bukti</a>
                @php
                    $extension = strtolower(pathinfo($invoice->proof_path, PATHINFO_EXTENSION));
                @endphp
                @if (in_array($extension, ['jpg', 'jpeg', 'png'], true))
                    <img src="{{ route('invoices.proof', $invoice) }}" alt="Bukti transfer" class="mt-4 max-h-96 w-full rounded-md object-contain ring-1 ring-slate-200">
                @endif
            @else
                <p class="mt-2 text-sm text-slate-500">Belum ada bukti yang diunggah.</p>
            @endif

            @if ($invoice->status === InvoiceStatus::WaitingVerification)
                <div class="mt-6 space-y-4">
                    <form method="POST" action="{{ route('admin.invoices.approve', $invoice) }}">
                        @csrf
                        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Tandai lunas</button>
                    </form>
                    <form method="POST" action="{{ route('admin.invoices.reject', $invoice) }}" class="space-y-2">
                        @csrf
                        <label for="rejection_reason" class="block text-sm font-medium text-slate-700">Alasan penolakan</label>
                        <textarea id="rejection_reason" name="rejection_reason" rows="2" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('rejection_reason') }}</textarea>
                        @error('rejection_reason') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        <button class="rounded-md border border-red-300 px-4 py-2 text-sm text-red-700 hover:bg-red-50">Tolak bukti</button>
                    </form>
                </div>
            @endif

            @if ($invoice->verified_at)
                <p class="mt-4 text-xs text-slate-500">
                    Diverifikasi {{ $invoice->verifier?->name }} pada {{ $invoice->verified_at->translatedFormat('d M Y H:i') }}.
                </p>
            @endif

            @if ($hasWithdrawnEntries && ! $competition->status->isSeededOrLater())
                <form method="POST" action="{{ route('admin.invoices.restore', $invoice) }}" class="mt-6" onsubmit="return confirm('Kembalikan entri yang dibatalkan karena lewat tempo?')">
                    @csrf
                    <button class="rounded-md border border-amber-300 bg-amber-50 px-4 py-2 text-sm text-amber-900 hover:bg-amber-100">Kembalikan entri lewat tempo</button>
                </form>
            @endif
        </div>
    </div>
@endsection
