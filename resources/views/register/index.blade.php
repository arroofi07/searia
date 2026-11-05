@extends('layouts.public')

@section('title', 'Pendaftaran peserta')
@section('meta_description', 'Daftarkan atlet ke kejuaraan renang yang sedang membuka pendaftaran. Tanpa perlu membuat akun.')

@section('content')
    @include('public._mast', [
        'kicker' => 'Untuk peserta',
        'title' => 'Daftar lomba tanpa akun',
        'lead' => 'Pilih kejuaraan, isi data peserta, pilih kode acara, lalu simpan kode pendaftaran. Setelah dikirim, perubahan hanya lewat panitia.',
    ])

    <ol class="steps" aria-label="Ringkasan alur">
        <li class="is-now">
            <span>01</span>
            <strong>Data peserta</strong>
            <p class="meta">Nama lengkap, L/P, tahun lahir, klub/sekolah, kabupaten/kota.</p>
        </li>
        <li>
            <span>02</span>
            <strong>Kode acara</strong>
            <p class="meta">Centang kode acara yang diikuti. Isi catatan waktu jika ada.</p>
        </li>
        <li>
            <span>03</span>
            <strong>Kirim &amp; simpan kode</strong>
            <p class="meta">Kode REG-… dipakai saat menghubungi panitia.</p>
        </li>
    </ol>

    <div class="gap-top">
        @forelse ($competitions as $competition)
            <article class="mast-row panel">
                <div>
                    <h2>{{ $competition->name }}</h2>
                    <p class="meta">{{ $competition->venue }}, {{ $competition->city }}</p>
                    <p class="meta">
                        {{ $competition->start_date->translatedFormat('d M Y') }}
                        @if (! $competition->start_date->equalTo($competition->end_date))
                            – {{ $competition->end_date->translatedFormat('d M Y') }}
                        @endif
                    </p>
                </div>
                <a href="{{ route('register.create', $competition) }}" class="public-btn">Daftar sekarang</a>
            </article>
        @empty
            <p class="note">Tidak ada kejuaraan yang membuka pendaftaran saat ini. Cek lagi nanti, atau lihat arsip hasil di menu atas.</p>
        @endforelse
    </div>

    @include('partials.pagination', ['paginator' => $competitions])
@endsection
