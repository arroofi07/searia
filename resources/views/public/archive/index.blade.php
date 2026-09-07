@extends('layouts.public')

@section('title', 'Arsip kejuaraan · SeaRIA')
@section('meta_description', 'Arsip hasil kejuaraan renang yang sudah dipublikasikan.')

@section('content')
    <h1 class="text-2xl font-semibold">Arsip kejuaraan</h1>
    <p class="mt-1 text-sm text-slate-500">Hanya kejuaraan dengan hasil yang sudah terbit.</p>

    <form method="GET" class="mt-6 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs text-slate-500">Tahun</label>
            <select name="year" class="mt-1 rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua tahun</option>
                @foreach ($years as $yearOption)
                    <option value="{{ $yearOption }}" @selected($selectedYear === (int) $yearOption)>{{ $yearOption }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Saring</button>
    </form>

    @forelse ($grouped as $year => $items)
        <h2 class="mt-8 text-lg font-semibold">{{ $year }}</h2>
        <div class="mt-3 space-y-3">
            @foreach ($items as $competition)
                <article class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3">
                    <div>
                        <h3 class="font-medium">{{ $competition->name }}</h3>
                        <p class="text-sm text-slate-500">
                            {{ $competition->venue }}, {{ $competition->city }}
                            · {{ $competition->start_date->translatedFormat('d M Y') }}
                            · {{ $competition->type->label() }}
                        </p>
                    </div>
                    <a href="{{ route('results.index', $competition) }}" class="text-sm font-medium text-teal-800 hover:underline">Hasil</a>
                </article>
            @endforeach
        </div>
    @empty
        <p class="mt-8 text-sm text-slate-500">Belum ada arsip hasil.</p>
    @endforelse

    <div class="mt-8">{{ $competitions->links() }}</div>
@endsection
