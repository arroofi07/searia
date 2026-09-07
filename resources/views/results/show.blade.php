@extends('layouts.public')

@section('title', $table->eventTitle)
@section('meta_description', $table->eventTitle.' · '.$table->ageGroupName.' · '.$competition->name)
@section('og_title', $table->eventTitle)

@section('content')
    <p class="text-sm text-slate-500"><a href="{{ route('results.index', $competition) }}" class="text-teal-800 hover:underline">← Semua hasil</a></p>
    <h1 class="mt-2 text-2xl font-semibold">{{ $table->eventTitle }}</h1>
    <p class="text-sm text-slate-500">{{ $table->ageGroupName }}</p>
    @if ($preview)
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">Pratinjau panitia</div>
    @endif
    <div class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white">
        @include('results._table', ['table' => $table, 'formatTime' => $formatTime, 'competition' => $competition])
    </div>
@endsection
