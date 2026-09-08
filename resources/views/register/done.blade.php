@extends('layouts.public')

@section('title', 'Pendaftaran terkirim')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="rounded-lg border border-teal-200 bg-teal-50 p-5">
            <h1 class="text-xl font-semibold text-teal-900">Pendaftaran terkirim</h1>
            <p class="mt-1 text-sm text-teal-900">
                Simpan atau potret halaman ini. Kode di bawah adalah satu-satunya rujukan Anda saat menghubungi panitia,
                dan halaman ini tidak dapat dibuka kembali setelah Anda menutup peramban.
            </p>
            <p class="mt-4 text-3xl font-bold tracking-widest text-teal-900">{{ $submission->code }}</p>
        </div>

        <div class="mt-6 rounded-lg border border-slate-200 bg-white p-5 text-sm">
            <h2 class="font-semibold">Rincian pendaftaran</h2>
            <dl class="mt-3 grid gap-2 sm:grid-cols-2">
                <div><dt class="text-slate-500">Kejuaraan</dt><dd>{{ $competition->name }}</dd></div>
                <div><dt class="text-slate-500">Atlet</dt><dd>{{ $submission->athlete->full_name }}</dd></div>
                <div><dt class="text-slate-500">Klub</dt><dd>{{ $submission->athlete->club?->name }}</dd></div>
                <div><dt class="text-slate-500">Pendaftar</dt><dd>{{ $submission->registrant_name }} · {{ $submission->registrant_phone }}</dd></div>
            </dl>

            <ul class="mt-4 space-y-1">
                @foreach ($submission->registrations as $registration)
                    <li>Acara {{ $registration->event?->event_number }} {{ $registration->event?->formattedName() }}</li>
                @endforeach
            </ul>
        </div>

        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            <p class="font-medium">Yang terjadi berikutnya</p>
            <ol class="mt-2 list-decimal space-y-1 pl-5">
                <li>Panitia memeriksa data atlet dan nomor lomba.</li>
                <li>Bila ada yang perlu diperbaiki, panitia menghubungi nomor WhatsApp Anda.</li>
                <li>Setelah diverifikasi, atlet masuk ke pembagian seri dan lintasan.</li>
            </ol>
            <p class="mt-3">Perubahan atau pembatalan entri dilakukan lewat panitia dengan menyebutkan kode pendaftaran.</p>
        </div>

        <a href="{{ route('register.index') }}" class="mt-6 inline-block text-sm text-teal-800 hover:underline">Daftarkan atlet lain</a>
    </div>
@endsection
