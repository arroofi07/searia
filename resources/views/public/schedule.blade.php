@extends('layouts.public')

@section('title', 'Jadwal · '.$competition->name)
@section('meta_description', 'Jadwal pendaftaran, technical meeting, dan susunan acara '.$competition->name)

@section('content')
    <div class="mx-auto max-w-4xl">
        <p class="text-sm text-slate-500"><a href="{{ route('home') }}" class="text-teal-800 hover:underline">Beranda</a></p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ $competition->name }}</h1>
        <p class="text-sm text-slate-500">{{ $competition->venue }}, {{ $competition->city }}</p>

        <ol class="mt-8 grid gap-3 sm:grid-cols-3">
            @foreach ($milestones as $milestone)
                <li class="rounded-xl border px-4 py-4 {{ $milestone['past'] ? 'border-slate-200 bg-slate-100 text-slate-500' : 'border-teal-200 bg-white shadow-sm' }}">
                    <h2 class="text-sm font-semibold">{{ $milestone['label'] }}</h2>
                    <p class="mt-2 text-sm">
                        @if ($milestone['start'] === null)
                            Belum dijadwalkan
                        @elseif ($milestone['end'] && $milestone['start']->equalTo($milestone['end']))
                            {{ $milestone['start']->timezone(config('app.timezone'))->translatedFormat('d F Y H:i') }}
                        @elseif ($milestone['label'] === 'Hari lomba')
                            {{ $milestone['start']->translatedFormat('d F Y') }}
                            @if (! $competition->start_date->equalTo($competition->end_date))
                                – {{ $competition->end_date->translatedFormat('d F Y') }}
                            @endif
                        @else
                            {{ $milestone['start']->timezone(config('app.timezone'))->translatedFormat('d F Y H:i') }}
                            – {{ $milestone['end']->timezone(config('app.timezone'))->translatedFormat('d F Y H:i') }}
                        @endif
                    </p>
                </li>
            @endforeach
        </ol>

        <section class="mt-10 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-900 px-4 py-4 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-teal-300">Susunan Acara Perlombaan</p>
                <p class="mt-1 text-sm text-white">{{ $competition->name }}</p>
            </div>

            @forelse ($programBySession as $session => $rows)
                <div class="@if (! $loop->first) border-t border-slate-200 @endif">
                    <h2 class="bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">Sesi {{ $session }}</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-teal-800 text-xs font-semibold uppercase tracking-wide text-white">
                                    <th class="w-20 px-3 py-3 text-center">PA</th>
                                    <th class="px-3 py-3 text-center">Nomor Perlombaan</th>
                                    <th class="w-20 px-3 py-3 text-center">PI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr class="border-t border-slate-100 {{ $loop->even ? 'bg-slate-50/80' : 'bg-white' }}">
                                        <td class="px-3 py-2.5 text-center font-mono font-semibold text-teal-900">
                                            {{ $row['pa']?->paddedEventNumber() ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2.5 text-center font-medium tracking-wide text-slate-900">
                                            {{ $row['label'] }}
                                        </td>
                                        <td class="px-3 py-2.5 text-center font-mono font-semibold text-teal-900">
                                            {{ $row['pi']?->paddedEventNumber() ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <p class="px-4 py-10 text-center text-sm text-slate-500">Nomor lomba belum dikonfigurasi.</p>
            @endforelse
        </section>

        <p class="mt-6 flex flex-wrap gap-4 text-sm">
            @if ($competition->status === \App\Enums\CompetitionStatus::Registration)
                <a href="{{ route('register.create', $competition) }}" class="rounded-md bg-teal-700 px-4 py-2 font-medium text-white hover:bg-teal-800">Daftar sekarang</a>
            @endif
            @if ($competition->status->isSeededOrLater())
                <a href="{{ route('start-list.show', $competition) }}" class="text-teal-800 hover:underline">Buku acara</a>
            @endif
            @if ($competition->status === \App\Enums\CompetitionStatus::Published)
                <a href="{{ route('results.index', $competition) }}" class="text-teal-800 hover:underline">Hasil</a>
            @endif
        </p>
    </div>
@endsection
