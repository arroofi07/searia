@extends('layouts.public')

@section('title', 'Buku acara · '.$competition->name)
@section('meta_description', 'Buku acara / start list '.$competition->name.' di '.$competition->venue.', '.$competition->city)
@section('og_title', 'Buku acara · '.$competition->name)

@section('content')
    <div class="mast-row">
        <div class="mast-id">
            @include('layouts.partials.event-logo', ['class' => 'meet-logo'])
            @include('public._mast', [
                'kicker' => 'Buku acara',
                'title' => 'Buku acara',
                'meta' => $competition->name.' · '.$competition->venue.', '.$competition->city,
                'lead' => 'Ini susunan seri dan lintasan. Kolom waktu adalah catatan waktu saat daftar (seed), bukan hasil lomba.',
            ])
        </div>
        <div class="toolbar">
            @include('public._books', ['competition' => $competition, 'primary' => 'start-list'])
            <a href="{{ route('start-list.pdf', $competition) }}" class="public-btn-secondary">Unduh PDF acara</a>
        </div>
    </div>

    <form method="GET" action="{{ route('start-list.show', $competition) }}" class="filters">
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Nomor acara</label>
            <select name="event_id" class="public-input">
                <option value="">Semua</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected((string) $filters['event_id'] === (string) $event->id)>
                        {{ $event->event_number }} {{ $event->formattedName() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Kelompok umur</label>
            <select name="age_group_id" class="public-input">
                <option value="">Semua</option>
                @foreach ($ageGroups as $group)
                    <option value="{{ $group->id }}" @selected((string) $filters['age_group_id'] === (string) $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Klub</label>
            <select name="club_id" class="public-input">
                <option value="">Semua</option>
                @foreach ($clubs as $club)
                    <option value="{{ $club->id }}" @selected((string) $filters['club_id'] === (string) $club->id)>{{ $club->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Cari nama</label>
            <input type="search" name="q" value="{{ $search }}" placeholder="Nama atlet" class="public-input">
        </div>
        <div class="span">
            <button class="public-btn">Terapkan saringan</button>
        </div>
    </form>

    @forelse ($document->sessions as $session)
        <h2 class="mt-8 text-lg font-semibold">Sesi {{ $session->session }}</h2>
        @foreach ($session->events as $event)
            <section class="program">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="font-medium">{{ $event->title() }}</h3>
                </div>
                @foreach ($event->ageGroups as $ageGroup)
                    <div class="border-b border-slate-100 bg-teal-50 px-4 py-2 text-sm font-medium text-teal-900">{{ $ageGroup->name }}</div>
                    @foreach ($ageGroup->heats as $heat)
                        <div class="px-4 pt-3 text-sm font-semibold text-slate-800">Seri {{ $heat->heatNumber }}</div>
                        <div class="overflow-x-auto px-0 pb-3 sm:px-2">
                            <table class="stack-table min-w-full text-left text-sm">
                                <thead class="text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="px-4 py-1 sm:px-2">Lintasan</th>
                                        <th class="px-2 py-1">Nama</th>
                                        <th class="px-2 py-1">Thn</th>
                                        <th class="px-2 py-1">KU</th>
                                        <th class="px-2 py-1">Klub</th>
                                        <th class="px-2 py-1">Kota</th>
                                        <th class="px-2 py-1">Catatan waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($heat->lanes as $lane)
                                        @php
                                            $highlight = $search !== '' && $lane->athleteName && str_contains(mb_strtoupper($lane->athleteName), mb_strtoupper($search));
                                        @endphp
                                        <tr class="{{ $highlight ? 'lane-hit bg-amber-50' : '' }}">
                                            <td class="px-4 py-1.5 font-medium sm:px-2" data-label="Lintasan">{{ $lane->laneNumber }}</td>
                                            @if ($lane->isEmpty())
                                                <td colspan="6" class="px-4 py-1.5 italic text-slate-400 sm:px-2" data-label="Nama">kosong</td>
                                            @else
                                                <td class="px-4 py-1.5 sm:px-2 {{ $highlight ? 'font-semibold text-amber-900' : '' }}" data-label="Nama">{{ $lane->athleteName }}</td>
                                                <td class="px-4 py-1.5 sm:px-2" data-label="Tahun lahir">{{ $lane->birthYear }}</td>
                                                <td class="px-4 py-1.5 sm:px-2" data-label="Kelompok umur">{{ $lane->ageGroupCode }}</td>
                                                <td class="px-4 py-1.5 sm:px-2" data-label="Klub">{{ $lane->clubName }}</td>
                                                <td class="px-4 py-1.5 sm:px-2" data-label="Kota">{{ $lane->city }}</td>
                                                <td class="px-4 py-1.5 font-mono sm:px-2" data-label="Catatan waktu">{{ $lane->formattedSeedTime() }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                @endforeach
            </section>
        @endforeach
    @empty
        <p class="mt-8 text-sm text-slate-500">Belum ada susunan seri untuk ditampilkan.</p>
    @endforelse

    @if ($eventPages)
        @include('partials.pagination', ['paginator' => $eventPages])
    @endif
@endsection
