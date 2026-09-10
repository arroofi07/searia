@extends('layouts.public')

@section('title', 'Arsip kejuaraan · SeaRIA')
@section('meta_description', 'Arsip hasil kejuaraan renang yang sudah dipublikasikan.')

@section('content')
    <h1 class="text-2xl font-semibold">Arsip kejuaraan</h1>
    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Hanya kejuaraan dengan hasil yang sudah terbit. Pilih tahun jika ingin mempersempit daftar.</p>

    <form method="GET" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="sm:w-48">
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Tahun</label>
            <select name="year" class="public-input">
                <option value="">Semua tahun</option>
                @foreach ($years as $yearOption)
                    <option value="{{ $yearOption }}" @selected($selectedYear === (int) $yearOption)>{{ $yearOption }}</option>
                @endforeach
            </select>
        </div>
        <button class="public-btn">Saring</button>
    </form>

    @forelse ($grouped as $year => $items)
        <h2 class="mt-8 text-lg font-semibold">{{ $year }}</h2>
        <div class="mt-3 space-y-3">
            @foreach ($items as $competition)
                <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-medium">{{ $competition->name }}</h3>
                            <p class="text-sm text-slate-500">
                                {{ $competition->venue }}, {{ $competition->city }}
                                · {{ $competition->start_date->translatedFormat('d M Y') }}
                                · {{ $competition->type->label() }}
                            </p>
                        </div>
                        <a href="{{ route('results.index', $competition) }}" class="inline-flex min-h-11 items-center font-medium text-teal-800 hover:underline">Lihat hasil</a>
                    </div>
                </article>
            @endforeach
        </div>
    @empty
        <p class="mt-8 text-sm text-slate-500">Belum ada arsip hasil.</p>
    @endforelse

    <div class="mt-8">{{ $competitions->links() }}</div>
@endsection
