@php
    use App\Support\SwimTime;

    $centerLanes = match ($laneCount) {
        4 => [2, 3],
        5 => [3],
        6 => [3, 4],
        8 => [4, 5],
        10 => [5, 6],
        default => [],
    };
@endphp

@extends('layouts.app')

@section('title', 'Susunan seri')

@section('content')
    <p class="text-sm"><a href="{{ route('admin.seeding.index', $competition) }}" class="text-teal-800 hover:underline">← Daftar pembagian seri</a></p>

    <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Nomor {{ $event->event_number }} {{ $event->formattedName() }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $competition->name }} · {{ $ageGroup->name }} · {{ $laneCount }} lintasan</p>
            <div class="mt-2">
                @include('admin.seeding._status', ['seeded' => $heats->total() > 0, 'locked' => $anyLocked])
            </div>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
            <form method="POST" action="{{ route('admin.seeding.run', $competition) }}">
                @csrf
                <input type="hidden" name="event_id" value="{{ $event->id }}">
                <input type="hidden" name="age_group_id" value="{{ $ageGroup->id }}">
                @if ($anyLocked)
                    <input type="hidden" name="force" value="1">
                @endif
                <button class="inline-flex min-h-11 w-full items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50 sm:w-auto"
                    @if ($anyLocked) onclick="return confirm('Nomor ini sudah dikunci. Ulangi pembagian akan mengganti susunan yang ada. Lanjutkan?')" @endif>
                    Ulangi pembagian nomor ini
                </button>
            </form>
            <form method="POST" action="{{ route('admin.seeding.lock', $competition) }}">
                @csrf
                <input type="hidden" name="event_id" value="{{ $event->id }}">
                <button class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800 sm:w-auto"
                    title="Mengunci semua seri nomor ini, termasuk kelompok umur lain">
                    Kunci nomor ini
                </button>
            </form>
        </div>
    </div>

    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'seeding'])

    @error('heat_lane')
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror
    @error('seeding')
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    <details class="mt-5 rounded-2xl border border-slate-200 bg-white p-4 text-sm leading-6 text-slate-700">
        <summary class="cursor-pointer font-semibold text-slate-900">Cara memeriksa halaman ini</summary>
        <ul class="mt-3 list-disc space-y-1 border-t border-slate-100 pt-3 pl-5">
            <li><strong>Catatan waktu</strong> di sini adalah waktu saat daftar (seed), bukan hasil lomba. NT = belum punya catatan waktu.</li>
            <li>Seri dengan nomor lebih besar biasanya berisi perenang lebih cepat. Lintasan tengah untuk yang lebih cepat dalam seri itu.</li>
            <li><strong>Tukar</strong> untuk saling menukar dua lintasan di seri yang sama. <strong>Keluarkan</strong> mengosongkan lintasan tanpa menggeser yang lain.</li>
            <li>Jika susunan sudah benar, tekan <strong>Kunci nomor ini</strong>. Setelah semua nomor dikunci, di Ringkasan lanjutkan status ke <strong>Sudah diseeding</strong>.</li>
        </ul>
    </details>

    @forelse ($heats as $heat)
        @php
            $filled = $heat->lanes->whereNotNull('registration_id')->count();
        @endphp
        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                <div>
                    <h2 class="font-semibold">Seri {{ $heat->heat_number }}</h2>
                    <p class="text-xs text-slate-500">{{ $filled }} dari {{ $laneCount }} lintasan terisi</p>
                </div>
                @include('admin.seeding._status', ['seeded' => true, 'locked' => $heat->isLocked()])
            </div>

            <div class="overflow-x-auto border-b border-slate-100 px-4 py-3">
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Peta lintasan · yang di tengah biasanya lebih cepat</p>
                <div class="flex min-w-max gap-1">
                    @for ($lane = 1; $lane <= $laneCount; $lane++)
                        @php
                            $heatLane = $heat->lanes->firstWhere('lane_number', $lane);
                            $registration = $heatLane?->registration;
                            $isCenter = in_array($lane, $centerLanes, true);
                        @endphp
                        <div class="w-24 rounded-lg border px-2 py-2 {{ $isCenter ? 'border-teal-300 bg-teal-50' : 'border-slate-200 bg-slate-50' }}">
                            <p class="text-[10px] font-semibold uppercase tracking-wide {{ $isCenter ? 'text-teal-800' : 'text-slate-500' }}">
                                L{{ $lane }}{{ $isCenter ? ' · tengah' : '' }}
                            </p>
                            @if ($registration)
                                <p class="mt-1 truncate text-xs font-medium text-slate-900" title="{{ $registration->athlete?->full_name }}">{{ $registration->athlete?->full_name }}</p>
                                <p class="font-mono text-[11px] text-slate-600">{{ SwimTime::formatMilliseconds($registration->seed_time_ms) }}</p>
                            @else
                                <p class="mt-1 text-xs italic text-slate-400">kosong</p>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="stack-table min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="w-16 px-4 py-2 font-medium">Lintasan</th>
                            <th class="px-4 py-2 font-medium">Nama</th>
                            <th class="px-4 py-2 font-medium">Klub</th>
                            <th class="px-4 py-2 font-medium">Kota</th>
                            <th class="px-4 py-2 font-medium">Catatan waktu</th>
                            <th class="px-4 py-2 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($lane = 1; $lane <= $laneCount; $lane++)
                            @php
                                $heatLane = $heat->lanes->firstWhere('lane_number', $lane);
                                $registration = $heatLane?->registration;
                                $athlete = $registration?->athlete;
                                $isCenter = in_array($lane, $centerLanes, true);
                            @endphp
                            <tr class="border-t border-slate-100 {{ $isCenter && $registration ? 'bg-teal-50/50' : '' }}">
                                <td class="px-4 py-2 font-medium" data-label="Lintasan">
                                    {{ $lane }}
                                    @if ($isCenter)
                                        <span class="ml-1 text-[10px] font-semibold uppercase text-teal-800">tengah</span>
                                    @endif
                                </td>
                                @if ($registration)
                                    <td class="px-4 py-2" data-label="Nama">{{ $athlete?->full_name }}</td>
                                    <td class="px-4 py-2" data-label="Klub">{{ $athlete?->club?->name }}</td>
                                    <td class="px-4 py-2" data-label="Kota">{{ $athlete?->club?->city }}</td>
                                    <td class="px-4 py-2 font-mono" data-label="Catatan waktu">{{ SwimTime::formatMilliseconds($registration->seed_time_ms) }}</td>
                                    <td class="px-4 py-2 text-right" data-label="Aksi">
                                        <form method="POST" action="{{ route('admin.heat-lanes.withdraw', $heatLane) }}" class="inline" onsubmit="return confirm('Keluarkan peserta dari lintasan? Lintasan akan dikosongkan, peserta lain tidak digeser.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-xs text-red-700 hover:underline">Keluarkan</button>
                                        </form>
                                    </td>
                                @else
                                    <td colspan="5" class="px-4 py-2 italic text-slate-400" data-label="Nama">kosong</td>
                                @endif
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            @if ($heat->lanes->whereNotNull('registration_id')->count() >= 2)
                <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tukar dua lintasan di seri ini</p>
                    <form method="POST" action="{{ route('admin.heat-lanes.swap') }}" class="mt-2 flex flex-col gap-2 text-sm sm:flex-row sm:flex-wrap sm:items-end">
                        @csrf
                        <div class="min-w-0 flex-1">
                            <label class="block text-xs text-slate-500">Peserta pertama</label>
                            <select name="left_lane_id" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-2 py-2">
                                @foreach ($heat->lanes->sortBy('lane_number') as $option)
                                    @if ($option->registration_id)
                                        <option value="{{ $option->id }}">Lintasan {{ $option->lane_number }} · {{ $option->registration?->athlete?->full_name ?? 'kosong' }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="min-w-0 flex-1">
                            <label class="block text-xs text-slate-500">Ditukar dengan</label>
                            <select name="right_lane_id" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-2 py-2">
                                @foreach ($heat->lanes->sortBy('lane_number') as $option)
                                    @if ($option->registration_id)
                                        <option value="{{ $option->id }}" @selected($loop->iteration === 2)>Lintasan {{ $option->lane_number }} · {{ $option->registration?->athlete?->full_name ?? 'kosong' }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <button class="rounded-md bg-slate-800 px-3 py-2 text-sm font-medium text-white hover:bg-slate-900">Tukar lintasan</button>
                    </form>
                </div>
            @endif
        </section>
    @empty
        <p class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-500">
            Belum ada seri untuk kombinasi ini. Kembali ke daftar, lalu tekan <strong>Bagi seri ini</strong>.
        </p>
    @endforelse

    @include('partials.pagination', ['paginator' => $heats])
@endsection
