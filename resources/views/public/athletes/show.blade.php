@extends('layouts.public')

@section('title', $athlete->full_name.' · SeaRIA')
@section('meta_description', 'Riwayat hasil '.$athlete->full_name.' di kejuaraan yang sudah dipublikasikan.')

@section('content')
    <p class="text-sm"><a href="{{ route('public.athletes.search') }}" class="text-teal-800 hover:underline">← Pencarian</a></p>
    <h1 class="mt-2 text-2xl font-semibold">{{ $athlete->full_name }}</h1>
    <p class="text-sm text-slate-500">{{ $athlete->club?->name }} · Tahun lahir {{ $athlete->birth_year }}</p>

    @if ($bestByEvent !== [])
        <section class="mt-8">
            <h2 class="text-lg font-semibold">Catatan terbaik per nomor</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($bestByEvent as $best)
                    <li class="rounded border border-slate-200 bg-white px-3 py-2">
                        {{ $best['label'] }} · {{ ($formatTime)($best['time_ms']) }}
                        <span class="text-slate-400">· {{ $best['competition'] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="mt-8">
        <h2 class="text-lg font-semibold">Riwayat lomba</h2>
        <div class="mt-3 overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Kejuaraan</th>
                        <th class="px-3 py-2">Acara</th>
                        <th class="px-3 py-2">Hasil</th>
                        <th class="px-3 py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($results as $result)
                        @php $event = $result->heatLane?->heat?->event; @endphp
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-2">
                                @if ($event?->competition)
                                    <a href="{{ route('results.index', $event->competition) }}" class="text-teal-800 hover:underline">{{ $event->competition->name }}</a>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $event?->event_number }} {{ $event?->formattedName() }}</td>
                            <td class="px-3 py-2">{{ ($formatTime)($result->time_ms) }}</td>
                            <td class="px-3 py-2">{{ $result->status->label() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-6 text-slate-500">Belum ada hasil publik.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
