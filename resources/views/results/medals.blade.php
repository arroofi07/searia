@extends('layouts.public')

@section('title', 'Rekap medali · '.$competition->name)
@section('meta_description', 'Rekap medali '.$competition->name)

@section('content')
    <div class="mast-row">
        <div>
            @include('public._mast', [
                'competition' => $competition,
                'back' => route('results.index', $competition),
                'backLabel' => 'Hasil',
                'kicker' => 'Penghargaan',
                'title' => 'Rekap medali',
                'meta' => $competition->name,
            ])
        </div>
        <div class="toolbar">
            @include('public._books', ['competition' => $competition, 'primary' => 'results'])
            <a href="{{ route('results.best-swimmers', $competition) }}" class="public-btn-secondary">Atlet terbaik</a>
            <a href="{{ route('results.best-club', $competition) }}" class="public-btn-secondary">Club terbaik</a>
        </div>
    </div>
    @if ($preview)
        <div class="note">Pratinjau panitia</div>
    @endif

    <nav class="toolbar">
        <a href="#per-nomor" class="text-teal-800 hover:underline">Per nomor lomba</a>
        <a href="#per-klub" class="text-teal-800 hover:underline">Per klub</a>
        <a href="#per-ku" class="text-teal-800 hover:underline">Per kelompok umur</a>
    </nav>

    <section id="per-klub" class="mt-8">
        <h2 class="text-lg font-semibold">Per klub</h2>
        <div class="program">
            <table class="stack-table min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Klub</th>
                        <th class="px-3 py-2">Emas</th>
                        <th class="px-3 py-2">Perak</th>
                        <th class="px-3 py-2">Perunggu</th>
                        <th class="px-3 py-2">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($byClub as $row)
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-2" data-label="Klub">{{ $row['club_name'] }}</td>
                            <td class="px-3 py-2" data-label="Emas">{{ $row['gold'] }}</td>
                            <td class="px-3 py-2" data-label="Perak">{{ $row['silver'] }}</td>
                            <td class="px-3 py-2" data-label="Perunggu">{{ $row['bronze'] }}</td>
                            <td class="px-3 py-2" data-label="Total">{{ $row['total'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-slate-500" data-label="Catatan">Belum ada medali.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.pagination', ['paginator' => $byClub])
    </section>

    <section id="per-ku" class="mt-8">
        <h2 class="text-lg font-semibold">Per kelompok umur</h2>
        <div class="program">
            <table class="stack-table min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Kelompok umur</th>
                        <th class="px-3 py-2">Emas</th>
                        <th class="px-3 py-2">Perak</th>
                        <th class="px-3 py-2">Perunggu</th>
                        <th class="px-3 py-2">Total</th>
                        <th class="px-3 py-2">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($byAgeGroup as $row)
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-2" data-label="Kelompok umur">{{ $row['age_group_name'] }}</td>
                            <td class="px-3 py-2" data-label="Emas">{{ $row['gold'] }}</td>
                            <td class="px-3 py-2" data-label="Perak">{{ $row['silver'] }}</td>
                            <td class="px-3 py-2" data-label="Perunggu">{{ $row['bronze'] }}</td>
                            <td class="px-3 py-2" data-label="Total">{{ $row['total'] }}</td>
                            <td class="px-3 py-2 text-xs text-amber-800" data-label="Catatan">
                                @if ($row['small_fields'] > 0)
                                    {{ $row['small_fields'] }} nomor &lt; 3 peserta
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-slate-500" data-label="Catatan">Belum ada medali.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.pagination', ['paginator' => $byAgeGroup])
    </section>

    <section id="per-nomor" class="mt-8">
        <h2 class="text-lg font-semibold">Per nomor lomba</h2>
        <div class="mt-3 space-y-4">
            @foreach ($blocks as $block)
                <section class="panel">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-medium">Acara {{ $block['event']->event_number }} · {{ $block['age_group']->name }}</h3>
                        <div class="text-sm text-slate-600">
                            Emas {{ $block['gold'] }} · Perak {{ $block['silver'] }} · Perunggu {{ $block['bronze'] }}
                            @if ($block['small_field'])
                                <span class="ml-2 rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-900">Kurang dari 3 peserta</span>
                            @endif
                        </div>
                    </div>
                    @if ($block['medals'] !== [])
                        <ul class="mt-3 space-y-1 text-sm">
                            @foreach ($block['medals'] as $medal)
                                <li>
                                    <span class="inline-block w-16 text-xs uppercase text-slate-500">{{ $medal['metal'] }}</span>
                                    {{ $medal['entry']->athleteName }} · {{ $medal['entry']->clubName }}
                                    · {{ ($formatTime)($medal['entry']->timeMs) }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach
        </div>
        @include('partials.pagination', ['paginator' => $blocks])
    </section>
@endsection
