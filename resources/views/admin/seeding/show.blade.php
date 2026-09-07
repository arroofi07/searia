@php
    use App\Support\SwimTime;
@endphp

@extends('layouts.app')

@section('title', 'Pratinjau seeding')

@section('content')
    <h1 class="text-2xl font-semibold">Acara {{ $event->event_number }} {{ $event->formattedName() }}</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }} · {{ $ageGroup->name }}</p>

    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'seeding'])

    @error('heat_lane')
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror
    @error('seeding')
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    <div class="mt-4 flex flex-wrap gap-3">
        <a href="{{ route('admin.seeding.index', $competition) }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Kembali</a>
        <form method="POST" action="{{ route('admin.seeding.run', $competition) }}">
            @csrf
            <input type="hidden" name="event_id" value="{{ $event->id }}">
            <input type="hidden" name="age_group_id" value="{{ $ageGroup->id }}">
            @if ($heats->contains(fn ($heat) => $heat->isLocked()))
                <input type="hidden" name="force" value="1">
            @endif
            <button class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Jalankan ulang</button>
        </form>
        <form method="POST" action="{{ route('admin.seeding.lock', $competition) }}">
            @csrf
            <input type="hidden" name="event_id" value="{{ $event->id }}">
            <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Kunci nomor ini</button>
        </form>
    </div>

    @forelse ($heats as $heat)
        <section class="mt-8 overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <h2 class="font-medium">Seri {{ $heat->heat_number }}</h2>
                <span class="text-xs text-slate-500">{{ $heat->isLocked() ? 'Terkunci' : 'Pratinjau' }}</span>
            </div>
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-2 font-medium w-16">Lintasan</th>
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
                                <td class="px-4 py-2">{{ SwimTime::formatMilliseconds($registration->seed_time_ms) }}</td>
                                <td class="px-4 py-2 text-right">
                                    <form method="POST" action="{{ route('admin.heat-lanes.withdraw', $heatLane) }}" class="inline" onsubmit="return confirm('Keluarkan peserta dari lintasan? Lintasan akan dikosongkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs text-red-700 hover:underline">Keluarkan</button>
                                    </form>
                                </td>
                            @else
                                <td colspan="5" class="px-4 py-2 text-slate-400 italic">kosong</td>
                            @endif
                        </tr>
                    @endfor
                </tbody>
            </table>

            @if ($heat->lanes->count() >= 2)
                <div class="border-t border-slate-100 px-4 py-3">
                    <form method="POST" action="{{ route('admin.heat-lanes.swap') }}" class="flex flex-wrap items-end gap-2 text-sm">
                        @csrf
                        <div>
                            <label class="block text-xs text-slate-500">Tukar</label>
                            <select name="left_lane_id" class="mt-1 rounded-md border border-slate-300 px-2 py-1">
                                @foreach ($heat->lanes->sortBy('lane_number') as $option)
                                    <option value="{{ $option->id }}">L{{ $option->lane_number }} · {{ $option->registration?->athlete?->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500">dengan</label>
                            <select name="right_lane_id" class="mt-1 rounded-md border border-slate-300 px-2 py-1">
                                @foreach ($heat->lanes->sortBy('lane_number') as $option)
                                    <option value="{{ $option->id }}">L{{ $option->lane_number }} · {{ $option->registration?->athlete?->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50">Tukar</button>
                    </form>
                </div>
            @endif
        </section>
    @empty
        <p class="mt-8 text-sm text-slate-500">Belum ada seri. Jalankan seeding terlebih dahulu.</p>
    @endforelse
@endsection
