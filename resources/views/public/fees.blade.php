@extends('layouts.public')

@section('title', 'Biaya · '.$competition->name)
@section('meta_description', 'Informasi biaya pendaftaran '.$competition->name)

@section('content')
    @include('public._mast', [
        'competition' => $competition,
        'back' => route('home'),
        'backLabel' => 'Beranda',
        'kicker' => 'Biaya',
        'title' => 'Biaya · '.$competition->name,
    ])

    @if (! $feeAvailable)
        <p class="note">Harga belum tersedia. Panitia belum menetapkan biaya per nomor lomba.</p>
    @else
        <dl class="facts">
            <div>
                <dt>Biaya per nomor lomba</dt>
                <dd>Rp {{ number_format($competition->fee_per_event, 0, ',', '.') }}</dd>
            </div>
            <div>
                <dt>Biaya keterlambatan / nomor</dt>
                <dd>
                    @if ((int) $competition->late_fee_per_event > 0)
                        Rp {{ number_format($competition->late_fee_per_event, 0, ',', '.') }}
                    @else
                        Tidak dikenakan
                    @endif
                </dd>
            </div>
        </dl>
    @endif

    <section class="sheet">
        <h2>Pembayaran</h2>
        <ul class="list-plain">
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

    <p class="gap-top"><a class="back" href="{{ route('public.competitions.schedule', $competition) }}">Lihat jadwal</a></p>
@endsection
