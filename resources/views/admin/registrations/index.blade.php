@php
    use App\Support\SwimTime;
@endphp

@extends('layouts.app')

@section('title', 'Verifikasi pendaftaran')

@section('content')
    <h1 class="text-2xl font-semibold">Antrean verifikasi</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }} · status pending</p>

    <div class="mt-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.registrations.create', $competition) }}" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Tambah manual</a>
        <a href="{{ route('admin.imports.index', $competition) }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Import Excel</a>
        <a href="{{ route('admin.submissions.index', $competition) }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Pendaftaran masuk</a>
    </div>

    <form method="GET" class="mt-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-3">
        <select name="club_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua klub</option>
            @foreach ($clubs as $club)
                <option value="{{ $club->id }}" @selected((string) ($filters['club_id'] ?? '') === (string) $club->id)>{{ $club->name }}</option>
            @endforeach
        </select>
        <select name="event_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua nomor</option>
            @foreach ($events as $event)
                <option value="{{ $event->id }}" @selected((string) ($filters['event_id'] ?? '') === (string) $event->id)>{{ $event->event_number }} {{ $event->shortName() }}</option>
            @endforeach
        </select>
        <select name="age_group_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua grup</option>
            @foreach ($ageGroups as $group)
                <option value="{{ $group->id }}" @selected((string) ($filters['age_group_id'] ?? '') === (string) $group->id)>{{ $group->name }}</option>
            @endforeach
        </select>
        <div class="sm:col-span-3">
            <button class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white">Saring</button>
        </div>
    </form>

    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-3 py-2"><input type="checkbox" id="check-all"></th>
                    <th class="px-3 py-2 font-medium">Atlet</th>
                    <th class="px-3 py-2 font-medium">Klub</th>
                    <th class="px-3 py-2 font-medium">Nomor</th>
                    <th class="px-3 py-2 font-medium">Grup</th>
                    <th class="px-3 py-2 font-medium">Waktu</th>
                    <th class="px-3 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registrations as $registration)
                    <tr class="border-t border-slate-100">
                        <td class="px-3 py-2"><input type="checkbox" value="{{ $registration->id }}" class="row-check"></td>
                        <td class="px-3 py-2">{{ $registration->athlete->full_name }}</td>
                        <td class="px-3 py-2">{{ $registration->athlete->club->name }}</td>
                        <td class="px-3 py-2">{{ $registration->event->event_number }} {{ $registration->event->shortName() }}</td>
                        <td class="px-3 py-2">{{ $registration->ageGroup->name }}</td>
                        <td class="px-3 py-2">{{ SwimTime::formatMilliseconds($registration->seed_time_ms) }}</td>
                        <td class="px-3 py-2">
                            <form method="POST" action="{{ route('admin.registrations.approve', $registration) }}">
                                @csrf
                                @method('PATCH')
                                <button class="text-teal-800 hover:underline">Setujui</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">Tidak ada antrean.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.registrations.bulk-approve', $competition) }}" class="mt-3" id="bulk-approve">
        @csrf
        <div class="bulk-ids"></div>
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Setujui yang dipilih</button>
    </form>

    <form method="POST" action="{{ route('admin.registrations.bulk-reject', $competition) }}" class="mt-6 max-w-xl space-y-3 rounded-lg border border-slate-200 bg-white p-5" id="bulk-reject">
        @csrf
        <h2 class="font-medium">Tolak yang dipilih</h2>
        <div class="bulk-ids"></div>
        <textarea name="rejection_reason" rows="3" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Alasan penolakan">{{ old('rejection_reason') }}</textarea>
        @error('rejection_reason') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        <button class="rounded-md bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800">Tolak yang dipilih</button>
    </form>

    <div class="mt-4">{{ $registrations->links() }}</div>

    <script>
        const boxes = [...document.querySelectorAll('.row-check')];
        document.getElementById('check-all')?.addEventListener('change', (event) => {
            boxes.forEach((box) => { box.checked = event.target.checked; });
        });
        function fillIds(form) {
            form.querySelector('.bulk-ids').innerHTML = '';
            boxes.filter((box) => box.checked).forEach((box) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'registration_ids[]';
                input.value = box.value;
                form.querySelector('.bulk-ids').appendChild(input);
            });
        }
        document.getElementById('bulk-approve')?.addEventListener('submit', (event) => fillIds(event.target));
        document.getElementById('bulk-reject')?.addEventListener('submit', (event) => fillIds(event.target));
    </script>
@endsection
