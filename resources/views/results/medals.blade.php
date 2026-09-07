@extends('layouts.public')

@section('title', 'Rekap medali · '.$competition->name)
@section('meta_description', 'Rekap medali '.$competition->name)

@section('content')
    <p class="text-sm"><a href="{{ route('results.index', $competition) }}" class="text-teal-800 hover:underline">← Hasil</a></p>
    <h1 class="mt-2 text-2xl font-semibold">Rekap medali</h1>
    <p class="text-sm text-slate-500">{{ $competition->name }}</p>
    @if ($preview)
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">Pratinjau panitia</div>
    @endif

    <nav class="mt-6 flex flex-wrap gap-3 text-sm">
        <a href="#per-nomor" class="text-teal-800 hover:underline">Per nomor lomba</a>
        <a href="#per-klub" class="text-teal-800 hover:underline">Per klub</a>
        <a href="#per-ku" class="text-teal-800 hover:underline">Per kelompok umur</a>
    </nav>

    <section id="per-klub" class="mt-8">
        <h2 class="text-lg font-semibold">Per klub</h2>
        <div class="mt-3 overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full text-left text-sm">
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
                            <td class="px-3 py-2">{{ $row['club_name'] }}</td>
                            <td class="px-3 py-2">{{ $row['gold'] }}</td>
                            <td class="px-3 py-2">{{ $row['silver'] }}</td>
                            <td class="px-3 py-2">{{ $row['bronze'] }}</td>
                            <td class="px-3 py-2">{{ $row['total'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-slate-500">Belum ada medali.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section id="per-ku" class="mt-8">
        <h2 class="text-lg font-semibold">Per kelompok umur</h2>
        <div class="mt-3 overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full text-left text-sm">
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
                            <td class="px-3 py-2">{{ $row['age_group_name'] }}</td>
                            <td class="px-3 py-2">{{ $row['gold'] }}</td>
                            <td class="px-3 py-2">{{ $row['silver'] }}</td>
                            <td class="px-3 py-2">{{ $row['bronze'] }}</td>
                            <td class="px-3 py-2">{{ $row['total'] }}</td>
                            <td class="px-3 py-2 text-xs text-amber-800">
                                @if ($row['small_fields'] > 0)
                                    {{ $row['small_fields'] }} nomor &lt; 3 peserta
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-slate-500">Belum ada medali.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section id="per-nomor" class="mt-8">
        <h2 class="text-lg font-semibold">Per nomor lomba</h2>
        <div class="mt-3 space-y-4">
            @foreach ($blocks as $block)
                <section class="rounded-lg border border-slate-200 bg-white p-4">
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
    </section>
@endsection
