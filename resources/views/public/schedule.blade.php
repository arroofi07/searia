@extends('layouts.public')

@section('title', 'Jadwal · '.$competition->name)
@section('meta_description', 'Jadwal pendaftaran, technical meeting, dan hari lomba '.$competition->name)

@section('content')
    <p class="text-sm text-slate-500"><a href="{{ route('home') }}" class="text-teal-800 hover:underline">Beranda</a></p>
    <h1 class="mt-2 text-2xl font-semibold">Jadwal · {{ $competition->name }}</h1>
    <p class="text-sm text-slate-500">{{ $competition->venue }}, {{ $competition->city }}</p>

    <ol class="mt-8 space-y-4">
        @foreach ($milestones as $milestone)
            <li class="rounded-xl border px-4 py-4 {{ $milestone['past'] ? 'border-slate-200 bg-slate-100 text-slate-500' : 'border-teal-200 bg-white' }}">
                <h2 class="font-medium">{{ $milestone['label'] }}</h2>
                <p class="mt-1 text-sm">
                    @if ($milestone['start'] === null)
                        Belum dijadwalkan
                    @elseif ($milestone['end'] && $milestone['start']->equalTo($milestone['end']))
                        {{ $milestone['start']->timezone(config('app.timezone'))->translatedFormat('l, d F Y H:i') }}
                    @elseif ($milestone['label'] === 'Hari lomba')
                        {{ $milestone['start']->translatedFormat('l, d F Y') }}
                        @if (! $competition->start_date->equalTo($competition->end_date))
                            – {{ $competition->end_date->translatedFormat('l, d F Y') }}
                        @endif
                    @else
                        {{ $milestone['start']->timezone(config('app.timezone'))->translatedFormat('d F Y H:i') }}
                        – {{ $milestone['end']->timezone(config('app.timezone'))->translatedFormat('d F Y H:i') }}
                    @endif
                    @if ($milestone['past'])
                        <span class="ml-2 text-xs uppercase tracking-wide">Selesai</span>
                    @endif
                </p>
            </li>
        @endforeach
    </ol>

    <section class="mt-10">
        <h2 class="text-lg font-semibold">Susunan acara</h2>
        @forelse ($eventsBySession as $session => $events)
            <div class="mt-4 rounded-lg border border-slate-200 bg-white">
                <h3 class="border-b border-slate-100 px-4 py-2 text-sm font-medium">Sesi {{ $session }}</h3>
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($events as $event)
                        <li class="px-4 py-2">Acara {{ $event->event_number }} · {{ $event->formattedName() }}</li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p class="mt-3 text-sm text-slate-500">Nomor lomba belum dikonfigurasi.</p>
        @endforelse
    </section>

    <p class="mt-6 flex flex-wrap gap-4 text-sm">
        <a href="{{ route('public.competitions.fees', $competition) }}" class="text-teal-800 hover:underline">Biaya</a>
        @if ($competition->status->isSeededOrLater())
            <a href="{{ route('start-list.show', $competition) }}" class="text-teal-800 hover:underline">Buku acara</a>
        @endif
        @if ($competition->status === \App\Enums\CompetitionStatus::Published)
            <a href="{{ route('results.index', $competition) }}" class="text-teal-800 hover:underline">Hasil</a>
        @endif
    </p>
@endsection
