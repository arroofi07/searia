@php
    use App\Support\SwimTime;

    $anyLocked = $heats->contains(fn ($heat) => $heat->isLocked());
@endphp

@extends('layouts.app')

@section('title', 'Susunan seri')

@section('content')
    <p class="text-sm"><a href="{{ route('admin.seeding.index', $competition) }}" class="text-teal-800 hover:underline">← Daftar pembagian seri</a></p>
    <h1 class="mt-2 text-2xl font-semibold">Nomor {{ $event->event_number }} {{ $event->formattedName() }}</h1>
    <p class="mt-1 text-sm text-slate-600">{{ $competition->name }} · {{ $ageGroup->name }} · {{ $laneCount }} lintasan</p>

    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'seeding'])

    @error('heat_lane')
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror
    @error('seeding')
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 text-sm leading-6 text-slate-700">
        <h2 class="font-semibold text-slate-900">Cara memeriksa halaman ini</h2>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li><strong>Catatan waktu</strong> di sini adalah waktu saat daftar (seed), bukan hasil lomba. NT = belum punya catatan waktu.</li>
            <li>Seri dengan nomor lebih besar biasanya berisi perenang lebih cepat. Lintasan tengah untuk yang lebih cepat dalam seri itu.</li>
            <li><strong>Tukar</strong> untuk saling menukar dua lintasan di seri yang sama. <strong>Keluarkan</strong> mengosongkan lintasan tanpa menggeser yang lain.</li>
            <li>Jika susunan sudah benar, tekan <strong>Kunci nomor ini</strong>. Setelah semua nomor dikunci, di Ringkasan lanjutkan status ke <strong>Sudah diseeding</strong>.</li>
        </ul>
    </section>

    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
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
            <button class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800 sm:w-auto">
                Kunci nomor ini
            </button>
        </form>
    </div>

    @forelse ($heats as $heat)
        <section class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <h2 class="font-medium">Seri {{ $heat->heat_number }}</h2>
                <span class="text-xs text-slate-500">{{ $heat->isLocked() ? 'Terkunci' : 'Pratinjau — belum dikunci' }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
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
                            @endphp
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-2 font-medium">{{ $lane }}</td>
                                @if ($registration)
                                    <td class="px-4 py-2">{{ $athlete?->full_name }}</td>
                                    <td class="px-4 py-2">{{ $athlete?->club?->name }}</td>
                                    <td class="px-4 py-2">{{ $athlete?->club?->city }}</td>
                                    <td class="px-4 py-2 font-mono">{{ SwimTime::formatMilliseconds($registration->seed_time_ms) }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <form method="POST" action="{{ route('admin.heat-lanes.withdraw', $heatLane) }}" class="inline" onsubmit="return confirm('Keluarkan peserta dari lintasan? Lintasan akan dikosongkan, peserta lain tidak digeser.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-xs text-red-700 hover:underline">Keluarkan</button>
                                        </form>
                                    </td>
                                @else
                                    <td colspan="5" class="px-4 py-2 italic text-slate-400">kosong</td>
                                @endif
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            @if ($heat->lanes->count() >= 2)
                <div class="border-t border-slate-100 px-4 py-3">
                    <p class="text-xs font-medium text-slate-500">Tukar dua lintasan di seri ini</p>
                    <form method="POST" action="{{ route('admin.heat-lanes.swap') }}" class="mt-2 flex flex-col gap-2 text-sm sm:flex-row sm:flex-wrap sm:items-end">
                        @csrf
                        <div>
                            <label class="block text-xs text-slate-500">Peserta pertama</label>
                            <select name="left_lane_id" class="mt-1 w-full rounded-md border border-slate-300 px-2 py-1.5 sm:w-auto">
                                @foreach ($heat->lanes->sortBy('lane_number') as $option)
                                    <option value="{{ $option->id }}">Lintasan {{ $option->lane_number }} · {{ $option->registration?->athlete?->full_name ?? 'kosong' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500">Ditukar dengan</label>
                            <select name="right_lane_id" class="mt-1 w-full rounded-md border border-slate-300 px-2 py-1.5 sm:w-auto">
                                @foreach ($heat->lanes->sortBy('lane_number') as $option)
                                    <option value="{{ $option->id }}">Lintasan {{ $option->lane_number }} · {{ $option->registration?->athlete?->full_name ?? 'kosong' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50">Tukar lintasan</button>
                    </form>
                </div>
            @endif
        </section>
    @empty
        <p class="mt-8 rounded-xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-500">
            Belum ada seri untuk kombinasi ini. Kembali ke daftar, lalu tekan <strong>Bagi seri ini</strong>.
        </p>
    @endforelse
@endsection
