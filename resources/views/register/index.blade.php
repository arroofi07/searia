@extends('layouts.public')

@section('title', 'Pendaftaran peserta')
@section('meta_description', 'Daftarkan atlet ke kejuaraan renang yang sedang membuka pendaftaran. Tanpa perlu membuat akun.')

@section('content')
    <div class="mx-auto max-w-2xl">
        <p class="text-sm font-semibold uppercase tracking-wide text-teal-800">Untuk peserta</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Daftar lomba tanpa akun</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600 sm:text-base">
            Pilih kejuaraan, isi data atlet, pilih nomor lomba, lalu simpan kode pendaftaran.
            Setelah dikirim, perubahan hanya lewat panitia.
        </p>

        <ol class="mt-6 grid gap-3 sm:grid-cols-3">
            <li class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">1</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">Data diri</p>
                <p class="mt-1 text-sm leading-5 text-slate-600">Kontak WhatsApp, nama atlet, klub, dan tahun lahir.</p>
            </li>
            <li class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">2</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">Pilih nomor</p>
                <p class="mt-1 text-sm leading-5 text-slate-600">Centang nomor yang diikuti. Isi catatan waktu jika ada.</p>
            </li>
            <li class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">3</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">Kirim &amp; simpan kode</p>
                <p class="mt-1 text-sm leading-5 text-slate-600">Kode <span class="font-mono">REG-…</span> dipakai saat menghubungi panitia.</p>
            </li>
        </ol>
    </div>

    <div class="mt-8 space-y-4">
        @forelse ($competitions as $competition)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ $competition->name }}</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $competition->venue }}, {{ $competition->city }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $competition->start_date->translatedFormat('d M Y') }}
                            @if (! $competition->start_date->equalTo($competition->end_date))
                                – {{ $competition->end_date->translatedFormat('d M Y') }}
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('register.create', $competition) }}" class="public-btn">Daftar sekarang</a>
                </div>
            </article>
        @empty
            <p class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center text-sm text-slate-500">
                Tidak ada kejuaraan yang membuka pendaftaran saat ini. Cek lagi nanti, atau lihat arsip hasil di menu atas.
            </p>
        @endforelse
    </div>
@endsection
