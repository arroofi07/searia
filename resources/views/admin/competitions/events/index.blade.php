@php
    use App\Enums\Equipment;
    use App\Enums\EventGender;
    use App\Enums\Stroke;
@endphp

@extends('layouts.app')

@section('title', 'Nomor lomba')

@section('content')
    <h1 class="text-2xl font-semibold">{{ $competition->name }}</h1>
    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'events'])

    <p class="mt-4 text-sm text-slate-500">Susunan baku Fun Swimming SeaRIA: 17 nomor lomba, 34 nomor acara (PA ganjil, PI genap).</p>

    <form method="POST" action="{{ route('admin.competitions.events.quick-fill', $competition) }}" class="mt-4">
        @csrf
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Isi susunan acara baku</button>
    </form>

    @if ($programRows !== [])
        <div class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 bg-slate-900 px-4 py-3 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-teal-300">Susunan Acara Perlombaan</p>
            </div>
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-teal-800 text-xs font-semibold uppercase tracking-wide text-white">
                        <th class="w-20 px-3 py-3 text-center">PA</th>
                        <th class="px-3 py-3 text-center">Nomor Perlombaan</th>
                        <th class="w-20 px-3 py-3 text-center">PI</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($programRows as $row)
                        <tr class="border-t border-slate-100 {{ $loop->even ? 'bg-slate-50/80' : 'bg-white' }}">
                            <td class="px-3 py-2.5 text-center font-mono font-semibold text-teal-900">{{ $row['pa']?->paddedEventNumber() ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-center font-medium tracking-wide">{{ $row['label'] }}</td>
                            <td class="px-3 py-2.5 text-center font-mono font-semibold text-teal-900">{{ $row['pi']?->paddedEventNumber() ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <p class="mt-6 text-sm text-slate-500">Nama nomor disusun dari jarak, gaya, alat bantu, dan gender. Seret baris untuk mengubah urutan tampil.</p>

    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">No</th>
                    <th class="px-4 py-3 font-medium">Nama</th>
                    <th class="px-4 py-3 font-medium">Sesi</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody id="event-rows" data-reorder-url="{{ route('admin.competitions.events.reorder', $competition) }}">
                @forelse ($competition->events as $event)
                    <tr draggable="true" data-id="{{ $event->id }}" class="border-t border-slate-100 cursor-grab">
                        <td class="px-4 py-3 font-medium">{{ $event->event_number }}</td>
                        <td class="px-4 py-3">{{ $event->formattedName() }}</td>
                        <td class="px-4 py-3">{{ $event->session }}</td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.competitions.events.destroy', [$competition, $event]) }}" onsubmit="return confirm('Hapus nomor lomba ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-700 hover:underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada nomor lomba.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.competitions.events.store', $competition) }}" class="mt-6 max-w-3xl space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        @csrf
        <h2 class="font-medium">Tambah nomor lomba</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Nomor acara</label>
                <input name="event_number" type="number" min="1" value="{{ old('event_number') }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('event_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Gender</label>
                <select name="gender" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @foreach (EventGender::cases() as $gender)
                        <option value="{{ $gender->value }}" @selected(old('gender') === $gender->value)>{{ $gender->label() }} ({{ $gender->value }})</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-slate-700">Jarak (m)</label>
                <select name="distance" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @foreach ([25, 50, 100, 200, 400] as $distance)
                        <option value="{{ $distance }}" @selected((string) old('distance', 50) === (string) $distance)>{{ $distance }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Gaya</label>
                <select name="stroke" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @foreach (Stroke::cases() as $stroke)
                        <option value="{{ $stroke->value }}" @selected(old('stroke') === $stroke->value)>{{ $stroke->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Alat bantu</label>
                <select name="equipment" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @foreach (Equipment::cases() as $equipment)
                        <option value="{{ $equipment->value }}" @selected(old('equipment', 'none') === $equipment->value)>{{ $equipment->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Sesi</label>
            <input name="session" type="number" min="1" value="{{ old('session', 1) }}" required class="mt-1 w-32 rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="create_pair" value="1" @checked(old('create_pair'))>
            Buat berpasangan (putra dan putri, nomor berikutnya untuk putri)
        </label>
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Tambah nomor</button>
    </form>

    <script>
        (() => {
            const body = document.getElementById('event-rows');
            if (!body) return;
            let dragged = null;

            body.querySelectorAll('tr[draggable]').forEach((row) => {
                row.addEventListener('dragstart', () => { dragged = row; row.classList.add('opacity-50'); });
                row.addEventListener('dragend', () => { row.classList.remove('opacity-50'); dragged = null; });
                row.addEventListener('dragover', (event) => { event.preventDefault(); });
                row.addEventListener('drop', (event) => {
                    event.preventDefault();
                    if (!dragged || dragged === row) return;
                    const rect = row.getBoundingClientRect();
                    const after = event.clientY > rect.top + rect.height / 2;
                    row.parentNode.insertBefore(dragged, after ? row.nextSibling : row);
                    persist();
                });
            });

            function persist() {
                const order = [...body.querySelectorAll('tr[data-id]')].map((row) => Number(row.dataset.id));
                fetch(body.dataset.reorderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ order }),
                });
            }
        })();
    </script>
@endsection
