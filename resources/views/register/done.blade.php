@extends('layouts.public')

@section('title', 'Pendaftaran terkirim')

@section('content')
    @include('public._mast', [
        'competition' => $competition,
        'kicker' => 'Berhasil dikirim',
        'title' => 'Pendaftaran terkirim',
        'lead' => 'Simpan atau potret halaman ini. Kode di bawah adalah rujukan Anda saat menghubungi panitia. Halaman ini tidak bisa dibuka lagi setelah peramban ditutup.',
    ])

    <p class="code-plate">{{ $submission->code }}</p>
    <p class="meta">Tunjukkan kode ini ke panitia jika ada perbaikan data.</p>

    <section class="sheet">
        <h2>Rincian pendaftaran</h2>
        <dl class="facts">
            <div><dt>Kejuaraan</dt><dd>{{ $competition->name }}</dd></div>
            <div><dt>Nama lengkap</dt><dd>{{ $submission->athlete->full_name }}</dd></div>
            <div><dt>L/P · Tahun lahir</dt><dd>{{ $submission->athlete->gender->value }} · {{ $submission->athlete->birth_year }}</dd></div>
            <div><dt>Klub/sekolah</dt><dd>{{ $submission->athlete->club?->name }}</dd></div>
            <div><dt>Kabupaten/kota</dt><dd>{{ $submission->athlete->club?->city ?: '—' }}</dd></div>
        </dl>
        <ul class="list-plain">
            @foreach ($submission->registrations as $registration)
                <li>Kode acara {{ $registration->event?->paddedEventNumber() }} · {{ $registration->event?->programName() }}</li>
            @endforeach
        </ul>
    </section>

    <section class="note">
        <h2>Apa yang terjadi selanjutnya?</h2>
        <ol>
            <li>Panitia memeriksa data peserta, kode acara, dan catatan waktu.</li>
            <li>Jika ada yang perlu diperbaiki, hubungi panitia dengan kode di atas.</li>
            <li>Setelah disetujui, peserta masuk ke pembagian seri dan lintasan.</li>
        </ol>
        <p>Perubahan atau pembatalan hanya lewat panitia, dengan menyebutkan kode pendaftaran.</p>
    </section>

    <div class="gap-top">
        <a href="{{ route('register.index') }}" class="public-btn">Daftarkan atlet lain</a>
    </div>
@endsection
