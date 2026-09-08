@extends('layouts.public')

@section('title', 'Hasil · '.$competition->name)
@section('meta_description', 'Hasil resmi '.$competition->name.($competition->published_at ? ' · dipublikasikan '.$competition->published_at->format('d/m/Y') : ''))
@section('og_title', 'Hasil · '.$competition->name)

@section('content')
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Hasil lomba</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $competition->name }}</p>
            @if ($competition->published_at)
                <p class="text-xs text-slate-500">Dipublikasikan {{ $competition->published_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <a href="{{ route('results.pdf', $competition) }}" class="rounded-md bg-teal-700 px-3 py-1.5 font-medium text-white hover:bg-teal-800">Unduh PDF hasil</a>
            <a href="{{ route('results.medals', $competition) }}" class="text-teal-800 hover:underline">Rekap medali</a>
            <a href="{{ route('results.standings', $competition) }}" class="text-teal-800 hover:underline">Klasemen klub</a>
        </div>
    </div>

    @if ($preview)
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">Pratinjau panitia — belum dipublikasikan.</div>
    @endif

    @forelse ($tables as $table)
        <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3">
                <h2 class="font-medium">{{ $table->eventTitle }}</h2>
                <p class="text-sm text-slate-500">{{ $table->ageGroupName }}</p>
            </div>
            @include('results._table', ['table' => $table, 'formatTime' => $formatTime, 'competition' => $competition])
        </section>
    @empty
        <p class="mt-8 text-sm text-slate-500">Belum ada hasil.</p>
    @endforelse
@endsection
