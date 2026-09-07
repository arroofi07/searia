@extends('layouts.public')

@section('title', 'Cari atlet · SeaRIA')
@section('meta_description', 'Cari atlet dan riwayat hasil kejuaraan yang sudah dipublikasikan.')

@section('content')
    <h1 class="text-2xl font-semibold">Cari atlet</h1>
    <p class="mt-1 text-sm text-slate-500">Hanya menampilkan hasil dari kejuaraan yang sudah terbit. Tahun lahir ditampilkan; data identitas pribadi tidak.</p>

    <form method="GET" action="{{ route('public.athletes.search') }}" class="mt-6 flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ $q }}" minlength="2" placeholder="Nama atlet (min. 2 huruf)" class="min-w-[16rem] flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm">
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Cari</button>
    </form>

    @if ($q !== '' && mb_strlen($q) < 2)
        <p class="mt-4 text-sm text-amber-800">Masukkan minimal dua huruf.</p>
    @endif

    <ul class="mt-6 divide-y divide-slate-100 overflow-hidden rounded-lg border border-slate-200 bg-white">
        @forelse ($athletes as $athlete)
            <li>
                <a href="{{ route('public.athletes.show', $athlete) }}" class="flex items-center justify-between gap-3 px-4 py-3 text-sm hover:bg-slate-50">
                    <span>
                        <span class="font-medium">{{ $athlete->full_name }}</span>
                        <span class="text-slate-500"> · {{ $athlete->club?->name }} · {{ $athlete->birth_year }}</span>
                    </span>
                    <span class="text-teal-800">Lihat</span>
                </a>
            </li>
        @empty
            @if (mb_strlen($q) >= 2)
                <li class="px-4 py-6 text-sm text-slate-500">Tidak ada atlet yang cocok.</li>
            @endif
        @endforelse
    </ul>
@endsection
