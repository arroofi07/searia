@extends('layouts.public')

@section('title', $athlete->full_name.' · Hasil')
@section('meta_description', 'Hasil '.$athlete->full_name.' pada '.$competition->name)

@section('content')
    <p class="text-sm"><a href="{{ route('results.index', $competition) }}" class="text-teal-800 hover:underline">← Hasil kejuaraan</a></p>
    <h1 class="mt-2 text-2xl font-semibold">{{ $athlete->full_name }}</h1>
    <p class="text-sm text-slate-500">{{ $competition->name }} · {{ $athlete->club?->name }}</p>
    @if ($preview)
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">Pratinjau</div>
    @endif

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <table class="stack-table min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-3 py-2">Acara</th>
                    <th class="px-3 py-2">KU</th>
                    <th class="px-3 py-2">Seed</th>
                    <th class="px-3 py-2">Hasil</th>
                    <th class="px-3 py-2">Selisih</th>
                    <th class="px-3 py-2">Peringkat</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="border-t border-slate-100">
                        <td class="px-3 py-2" data-label="Acara">
                            @if ($row['event'])
                                {{ $row['event']->event_number }} {{ $row['event']->formattedName() }}
                            @endif
                            <div class="text-xs text-slate-400">Seri {{ $row['heat_number'] }} Lint {{ $row['lane_number'] }}</div>
                        </td>
                        <td class="px-3 py-2" data-label="Kelompok umur">{{ $row['age_group']?->name }}</td>
                        <td class="px-3 py-2 font-mono" data-label="Catatan waktu">{{ ($formatTime)($row['seed_ms']) }}</td>
                        <td class="px-3 py-2" data-label="Hasil">
                            {{ ($formatTime)($row['time_ms']) }}
                            @if ($row['is_pb'])
                                <span class="ml-1 rounded bg-teal-100 px-1.5 py-0.5 text-[10px] font-semibold text-teal-800">PB</span>
                            @endif
                            @if ($row['status'] !== \App\Enums\ResultStatus::Ok)
                                <span class="text-xs text-slate-500">{{ $row['status']->label() }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2" data-label="Selisih">
                            @if ($row['delta_ms'] === null)
                                —
                            @elseif ($row['delta_ms'] < 0)
                                {{ ($formatTime)(abs($row['delta_ms'])) }} lebih cepat
                            @elseif ($row['delta_ms'] > 0)
                                +{{ ($formatTime)($row['delta_ms']) }}
                            @else
                                sama
                            @endif
                        </td>
                        <td class="px-3 py-2" data-label="Peringkat">{{ $row['rank'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($history->isNotEmpty())
        <h2 class="mt-8 text-lg font-semibold">Riwayat kejuaraan lain</h2>
        <ul class="mt-3 space-y-2 text-sm">
            @foreach ($history as $item)
                <li class="rounded border border-slate-200 bg-white px-3 py-2">
                    {{ $item->heatLane?->heat?->event?->competition?->name }}
                    · Acara {{ $item->heatLane?->heat?->event?->event_number }}
                    · {{ ($formatTime)($item->time_ms) }}
                    · {{ $item->status->label() }}
                </li>
            @endforeach
        </ul>
    @endif
@endsection
