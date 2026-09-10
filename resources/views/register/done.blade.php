@extends('layouts.public')

@section('title', 'Pendaftaran terkirim')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="rounded-2xl border border-teal-200 bg-gradient-to-br from-teal-50 to-white p-5 sm:p-6">
            <p class="text-sm font-semibold uppercase tracking-wide text-teal-800">Berhasil dikirim</p>
            <h1 class="mt-1 text-xl font-semibold text-teal-950 sm:text-2xl">Pendaftaran terkirim</h1>
            <p class="mt-2 text-sm leading-6 text-teal-950">
                Simpan atau potret halaman ini. Kode di bawah adalah rujukan Anda saat menghubungi panitia.
                Halaman ini tidak bisa dibuka lagi setelah peramban ditutup.
            </p>
            <p class="mt-5 rounded-2xl bg-white px-4 py-4 text-center font-mono text-3xl font-bold tracking-[0.2em] text-teal-900 sm:text-4xl">{{ $submission->code }}</p>
            <p class="mt-2 text-center text-xs text-teal-800">Tunjukkan kode ini ke panitia jika ada perbaikan data.</p>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 text-sm shadow-sm">
            <h2 class="font-semibold text-slate-900">Rincian pendaftaran</h2>
            <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kejuaraan</dt><dd class="mt-0.5">{{ $competition->name }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Atlet</dt><dd class="mt-0.5">{{ $submission->athlete->full_name }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Klub</dt><dd class="mt-0.5">{{ $submission->athlete->club?->name }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pendaftar</dt><dd class="mt-0.5">{{ $submission->registrant_name }} · {{ $submission->registrant_phone }}</dd></div>
            </dl>

            <ul class="mt-4 space-y-2">
                @foreach ($submission->registrations as $registration)
                    <li class="rounded-xl bg-slate-50 px-3 py-2">
                        Nomor {{ $registration->event?->paddedEventNumber() }} · {{ $registration->event?->programName() }}
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-950">
            <p class="font-semibold">Apa yang terjadi selanjutnya?</p>
            <ol class="mt-2 list-decimal space-y-1.5 pl-5">
                <li>Panitia memeriksa data atlet dan nomor lomba.</li>
                <li>Jika ada yang perlu diperbaiki, panitia menghubungi WhatsApp Anda.</li>
                <li>Setelah disetujui, atlet masuk ke pembagian seri dan lintasan.</li>
            </ol>
            <p class="mt-3">Perubahan atau pembatalan hanya lewat panitia, dengan menyebutkan kode pendaftaran.</p>
        </div>

        <a href="{{ route('register.index') }}" class="public-btn mt-6">Daftarkan atlet lain</a>
    </div>
@endsection
