<table class="min-w-full text-left text-sm">
    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
        <tr>
            <th class="px-3 py-2">Peringkat</th>
            <th class="px-3 py-2">Nama</th>
            <th class="px-3 py-2">Klub</th>
            <th class="px-3 py-2">Kota</th>
            <th class="px-3 py-2">Waktu</th>
            <th class="px-3 py-2">Selisih</th>
            <th class="px-3 py-2">Seri</th>
            <th class="px-3 py-2">Lint</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($table->entries as $entry)
            @php
                $podium = $entry->isPodium();
                $rowClass = match ($entry->rank) {
                    1 => 'bg-amber-50',
                    2 => 'bg-slate-100',
                    3 => 'bg-orange-50',
                    default => '',
                };
            @endphp
            <tr class="border-t border-slate-100 {{ $rowClass }}">
                <td class="px-3 py-2 font-medium {{ $podium ? 'text-teal-900' : '' }}">
                    {{ $entry->rank ?? '—' }}
                    @if ($entry->status !== \App\Enums\ResultStatus::Ok)
                        <span class="text-xs font-normal text-slate-500">{{ $entry->status->label() }}</span>
                    @endif
                </td>
                <td class="px-3 py-2">
                    @if ($entry->athleteId)
                        <a href="{{ route('results.athlete', [$competition, $entry->athleteId]) }}" class="hover:underline">{{ $entry->athleteName }}</a>
                    @else
                        {{ $entry->athleteName }}
                    @endif
                    @if ($entry->isPersonalBest)
                        <span class="ml-1 rounded bg-teal-100 px-1.5 py-0.5 text-[10px] font-semibold text-teal-800">PB</span>
                    @endif
                </td>
                <td class="px-3 py-2">{{ $entry->clubName }}</td>
                <td class="px-3 py-2">{{ $entry->city ?? '—' }}</td>
                <td class="px-3 py-2">{{ ($formatTime)($entry->timeMs) }}</td>
                <td class="px-3 py-2 text-slate-600">
                    @if ($entry->gapToFirstMs === null)
                        —
                    @elseif ($entry->gapToFirstMs === 0)
                        —
                    @else
                        +{{ ($formatTime)($entry->gapToFirstMs) }}
                    @endif
                </td>
                <td class="px-3 py-2">{{ $entry->heatNumber }}</td>
                <td class="px-3 py-2">{{ $entry->laneNumber }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
