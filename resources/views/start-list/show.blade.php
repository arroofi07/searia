@extends('layouts.public')

@section('title', 'Buku acara · '.$competition->name)
@section('meta_description', 'Buku acara / start list '.$competition->name.' di '.$competition->venue.', '.$competition->city)
@section('og_title', 'Buku acara · '.$competition->name)

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Buku acara</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $competition->name }} · {{ $competition->venue }}, {{ $competition->city }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('start-list.show', $competition) }}" class="mt-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-4">
        <div>
            <label class="block text-xs font-medium text-slate-500">Nomor acara</label>
            <select name="event_id" class="mt-1 w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                <option value="">Semua</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected((string) $filters['event_id'] === (string) $event->id)>
                        {{ $event->event_number }} {{ $event->formattedName() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Kelompok umur</label>
            <select name="age_group_id" class="mt-1 w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                <option value="">Semua</option>
                @foreach ($ageGroups as $group)
                    <option value="{{ $group->id }}" @selected((string) $filters['age_group_id'] === (string) $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Klub</label>
            <select name="club_id" class="mt-1 w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                <option value="">Semua</option>
                @foreach ($clubs as $club)
                    <option value="{{ $club->id }}" @selected((string) $filters['club_id'] === (string) $club->id)>{{ $club->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Cari nama</label>
            <input type="search" name="q" value="{{ $search }}" placeholder="Nama atlet" class="mt-1 w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
        </div>
        <div class="sm:col-span-4">
            <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Terapkan</button>
        </div>
    </form>

    @forelse ($document->sessions as $session)
        <h2 class="mt-8 text-lg font-semibold">Sesi {{ $session->session }}</h2>
        @foreach ($session->events as $event)
            <section class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="font-medium">{{ $event->title() }}</h3>
                </div>
                @foreach ($event->ageGroups as $ageGroup)
                    <div class="border-b border-slate-100 px-4 py-2 text-sm font-medium text-teal-900 bg-teal-50">{{ $ageGroup->name }}</div>
                    @foreach ($ageGroup->heats as $heat)
                        <div class="px-4 pt-3 text-sm font-medium">Seri {{ $heat->heatNumber }}</div>
                        <div class="overflow-x-auto px-2 pb-3">
                            <table class="min-w-full text-left text-sm">
                                <thead class="text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="px-2 py-1">Lintasan</th>
                                        <th class="px-2 py-1">Nama</th>
                                        <th class="px-2 py-1">Thn</th>
                                        <th class="px-2 py-1">KU</th>
                                        <th class="px-2 py-1">Klub</th>
                                        <th class="px-2 py-1">Kota</th>
                                        <th class="px-2 py-1">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($heat->lanes as $lane)
                                        @php
                                            $highlight = $search !== '' && $lane->athleteName && str_contains(mb_strtoupper($lane->athleteName), mb_strtoupper($search));
                                        @endphp
                                        <tr class="border-t border-slate-100 {{ $highlight ? 'bg-amber-50' : '' }}">
                                            <td class="px-2 py-1.5 font-medium">{{ $lane->laneNumber }}</td>
                                            @if ($lane->isEmpty())
                                                <td colspan="6" class="px-2 py-1.5 italic text-slate-400">kosong</td>
                                            @else
                                                <td class="px-2 py-1.5 {{ $highlight ? 'font-semibold text-amber-900' : '' }}">{{ $lane->athleteName }}</td>
                                                <td class="px-2 py-1.5">{{ $lane->birthYear }}</td>
                                                <td class="px-2 py-1.5">{{ $lane->ageGroupCode }}</td>
                                                <td class="px-2 py-1.5">{{ $lane->clubName }}</td>
                                                <td class="px-2 py-1.5">{{ $lane->city }}</td>
                                                <td class="px-2 py-1.5">{{ $lane->formattedSeedTime() }}</td>
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
@endsection
