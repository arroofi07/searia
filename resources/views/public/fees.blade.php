@extends('layouts.public')

@section('title', 'Biaya · '.$competition->name)
@section('meta_description', 'Informasi biaya pendaftaran '.$competition->name)

@section('content')
    <p class="text-sm text-slate-500"><a href="{{ route('home') }}" class="text-teal-800 hover:underline">Beranda</a></p>
    <h1 class="mt-2 text-2xl font-semibold">Biaya · {{ $competition->name }}</h1>

    @if (! $feeAvailable)
        <p class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            Harga belum tersedia. Panitia belum menetapkan biaya per nomor lomba.
        </p>
    @else
        <dl class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <dt class="text-xs uppercase text-slate-500">Biaya per nomor lomba</dt>
                <dd class="mt-1 text-2xl font-semibold">Rp {{ number_format($competition->fee_per_event, 0, ',', '.') }}</dd>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <dt class="text-xs uppercase text-slate-500">Biaya keterlambatan / nomor</dt>
                <dd class="mt-1 text-2xl font-semibold">
                    @if ((int) $competition->late_fee_per_event > 0)
                        Rp {{ number_format($competition->late_fee_per_event, 0, ',', '.') }}
                    @else
                        Tidak dikenakan
                    @endif
                </dd>
            </div>
        </dl>
    @endif

    <section class="mt-8 rounded-xl border border-slate-200 bg-white p-5 text-sm">
        <h2 class="font-medium">Pembayaran</h2>
        <ul class="mt-3 space-y-1 text-slate-700">
            <li>Batas waktu pembayaran: {{ $dueDays }} hari setelah tagihan diterbitkan.</li>
            @if ($bank['name'] || $bank['account'])
                <li>Bank: {{ $bank['name'] ?: '—' }}</li>
                <li>No. rekening: {{ $bank['account'] ?: '—' }}</li>
                <li>Atas nama: {{ $bank['holder'] ?: '—' }}</li>
            @else
                <li>Informasi rekening akan diumumkan bersamaan dengan penerbitan tagihan.</li>
            @endif
        </ul>
    </section>

    <p class="mt-6 text-sm">
        <a href="{{ route('public.competitions.schedule', $competition) }}" class="text-teal-800 hover:underline">Lihat jadwal</a>
    </p>
@endsection
