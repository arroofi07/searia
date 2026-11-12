@extends('layouts.public')

@section('title', 'Arsip kejuaraan · SeaRIA')
@section('meta_description', 'Arsip hasil kejuaraan renang yang sudah dipublikasikan.')

@section('content')
    @include('public._mast', [
        'kicker' => '04 — Arsip',
        'title' => 'Arsip kejuaraan',
        'lead' => 'Hanya kejuaraan dengan hasil yang sudah terbit. Pilih tahun jika ingin mempersempit daftar.',
    ])

    <form method="GET" class="filters">
        <div>
            <label for="year">Tahun</label>
            <select id="year" name="year" class="public-input">
                <option value="">Semua tahun</option>
                @foreach ($years as $yearOption)
                    <option value="{{ $yearOption }}" @selected($selectedYear === (int) $yearOption)>{{ $yearOption }}</option>
                @endforeach
            </select>
        </div>
        <div class="span">
            <button class="public-btn">Saring</button>
        </div>
    </form>

    @forelse ($grouped as $year => $items)
        <h2 class="gap-top">{{ $year }}</h2>
        @foreach ($items as $competition)
            <article class="list-plain">
                <div class="mast-row">
                    @include('layouts.partials.event-logo', [
                        'competition' => $competition,
                        'class' => 'meet-logo',
                        'alt' => $competition->name,
                    ])
                    <div>
                        <h3>{{ $competition->name }}</h3>
                        <p class="meta">
                            {{ $competition->venue }}, {{ $competition->city }}
                            · {{ $competition->start_date->translatedFormat('d M Y') }}
                            · {{ $competition->type->label() }}
                        </p>
                    </div>
                    @include('public._books', ['competition' => $competition, 'primary' => 'results'])
                </div>
            </article>
        @endforeach
    @empty
        <p class="note">Belum ada arsip hasil.</p>
    @endforelse

    @include('partials.pagination', ['paginator' => $competitions])
@endsection
