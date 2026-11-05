@extends('layouts.public')

@section('title', $athlete->full_name.' · Hasil')
@section('meta_description', 'Hasil '.$athlete->full_name.' pada '.$competition->name)

@section('content')
    @include('public._mast', [
        'back' => route('results.index', $competition),
        'backLabel' => 'Hasil kejuaraan',
        'kicker' => 'Atlet',
        'title' => $athlete->full_name,
        'meta' => $competition->name.' · '.($athlete->club?->name ?? ''),
    ])
    @if ($competition->hasPublicResults() && $competition->status !== \App\Enums\CompetitionStatus::Published)
        <div class="note">Hasil sementara — lomba belum dipublikasikan.</div>
    @elseif ($preview)
        <div class="note">Pratinjau</div>
    @endif

    <div class="program">
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
                                <span class="pb">PB</span>
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

    @include('partials.pagination', ['paginator' => $rows])

    @if ($history->isNotEmpty())
        <h2 class="mt-8 text-lg font-semibold">Riwayat kejuaraan lain</h2>
        <ul class="mt-3 space-y-2 text-sm">
            @foreach ($history as $item)
                <li class="panel">
                    {{ $item->heatLane?->heat?->event?->competition?->name }}
                    · Acara {{ $item->heatLane?->heat?->event?->event_number }}
                    · {{ ($formatTime)($item->time_ms) }}
                    · {{ $item->status->label() }}
                </li>
            @endforeach
        </ul>
    @endif
@endsection
