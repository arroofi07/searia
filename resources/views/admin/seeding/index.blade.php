@extends('layouts.app')

@section('title', 'Seeding')

@section('content')
    <h1 class="text-2xl font-semibold">Seeding seri dan lintasan</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }} · {{ $competition->pool_lanes }} lintasan · {{ $competition->seeding_mode->label() }}</p>

    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'seeding'])

    @error('seeding')
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    <dl class="mt-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-3 text-sm">
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Kombinasi nomor × grup</dt>
            <dd class="mt-1 text-lg font-semibold">{{ count($pairs) }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Belum diseeding</dt>
            <dd class="mt-1 text-lg font-semibold {{ $unseeded > 0 ? 'text-amber-700' : 'text-teal-800' }}">{{ $unseeded }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Sudah diseeding, belum dikunci</dt>
            <dd class="mt-1 text-lg font-semibold">{{ $unlocked }}</dd>
        </div>
    </dl>

    <div class="mt-4 flex flex-wrap gap-3">
        <form method="POST" action="{{ route('admin.seeding.run', $competition) }}">
            @csrf
            <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Jalankan seeding seluruh kejuaraan</button>
        </form>
        <form method="POST" action="{{ route('admin.seeding.lock', $competition) }}" onsubmit="return confirm('Kunci seluruh seri yang sudah diseeding?')">
            @csrf
            <button class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Kunci seluruh kejuaraan</button>
        </form>
    </div>

    <div class="mt-8 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Nomor</th>
                    <th class="px-4 py-3 font-medium">Kelompok umur</th>
                    <th class="px-4 py-3 font-medium">Seri</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pairs as $pair)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">{{ $pair['event']->event_number }} {{ $pair['event']->formattedName() }}</td>
                        <td class="px-4 py-3">{{ $pair['ageGroup']->name }}</td>
                        <td class="px-4 py-3">{{ $pair['heatCount'] }}</td>
                        <td class="px-4 py-3">
                            @if (! $pair['seeded'])
                                <span class="text-amber-700">Belum diseeding</span>
                            @elseif ($pair['locked'])
                                <span class="text-teal-800">Terkunci</span>
                            @else
                                <span class="text-slate-700">Pratinjau</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-3">
                            @if ($pair['seeded'])
                                <a href="{{ route('admin.seeding.show', [$competition, $pair['event'], $pair['ageGroup']]) }}" class="text-teal-800 hover:underline">Pratinjau</a>
                            @endif
                            <form method="POST" action="{{ route('admin.seeding.run', $competition) }}" class="inline">
                                @csrf
                                <input type="hidden" name="event_id" value="{{ $pair['event']->id }}">
                                <input type="hidden" name="age_group_id" value="{{ $pair['ageGroup']->id }}">
                                @if ($pair['locked'])
                                    <input type="hidden" name="force" value="1">
                                @endif
                                <button class="text-teal-800 hover:underline">{{ $pair['seeded'] ? 'Ulangi' : 'Seeding' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada nomor lomba dengan kelompok umur.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
