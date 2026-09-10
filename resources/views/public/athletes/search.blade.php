@extends('layouts.public')

@section('title', 'Cari atlet · SeaRIA')
@section('meta_description', 'Cari atlet dan riwayat hasil kejuaraan yang sudah dipublikasikan.')

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="text-2xl font-semibold">Cari atlet</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Cari nama atlet pada kejuaraan yang hasilnya sudah terbit. Yang tampil: nama, klub, dan tahun lahir.
            Nomor identitas dan tanggal lahir lengkap tidak ditampilkan.
        </p>

        <form method="GET" action="{{ route('public.athletes.search') }}" class="mt-6 flex flex-col gap-3 sm:flex-row">
            <input type="search" name="q" value="{{ $q }}" minlength="2" placeholder="Nama atlet (min. 2 huruf)" class="public-input mt-0 flex-1">
            <button class="public-btn">Cari</button>
        </form>

        @if ($q !== '' && mb_strlen($q) < 2)
            <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">Masukkan minimal dua huruf.</p>
        @endif

        <ul class="mt-6 divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            @forelse ($athletes as $athlete)
                <li>
                    <a href="{{ route('public.athletes.show', $athlete) }}" class="flex min-h-14 items-center justify-between gap-3 px-4 py-3 text-sm hover:bg-slate-50">
                        <span>
                            <span class="font-medium">{{ $athlete->full_name }}</span>
                            <span class="mt-0.5 block text-slate-500">{{ $athlete->club?->name }} · lahir {{ $athlete->birth_year }}</span>
                        </span>
                        <span class="font-medium text-teal-800">Lihat</span>
                    </a>
                </li>
            @empty
                @if (mb_strlen($q) >= 2)
                    <li class="px-4 py-6 text-sm text-slate-500">Tidak ada atlet yang cocok.</li>
                @endif
            @endforelse
        </ul>
    </div>
@endsection
