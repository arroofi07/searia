@php
    use App\Support\SwimTime;

    $pendingTotal = $registrations->total();
    $hasFilters = collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
    $approveAllLabel = $hasFilters
        ? 'Setujui semua hasil saringan ('.$pendingTotal.')'
        : 'Setujui semua antrean ('.$pendingTotal.')';
    $approveAllConfirm = $hasFilters
        ? 'Setujui '.$pendingTotal.' pendaftaran sesuai saringan yang tampil?'
        : 'Setujui seluruh '.$pendingTotal.' pendaftaran pending di antrean ini?';
@endphp

@extends('layouts.app')

@section('title', 'Pendaftaran')

@section('content')
    <h1 class="text-2xl font-semibold">Pendaftaran</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }} · antrean status pending</p>

    @include('admin.registrations._tabs', ['competition' => $competition, 'current' => 'registrations'])

    @error('status')
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

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

    @if ($pendingTotal > 0)
        <div class="mt-4 flex flex-col gap-3 rounded-2xl border border-teal-200 bg-teal-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm leading-6 text-teal-950">
                <p class="font-semibold">{{ number_format($pendingTotal, 0, ',', '.') }} pendaftaran menunggu persetujuan</p>
                <p>
                    @if ($hasFilters)
                        Tombol di kanan menyetujui semua baris yang cocok dengan saringan, termasuk halaman berikutnya.
                    @else
                        Pilih baris di tabel, atau setujui seluruh antrean sekaligus.
                    @endif
                </p>
            </div>
            <form method="POST" action="{{ route('admin.registrations.approve-all', $competition) }}" onsubmit="return confirm(@json($approveAllConfirm))">
                @csrf
                @foreach ($filters as $name => $value)
                    @if ($value !== null && $value !== '')
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endif
                @endforeach
                <button class="inline-flex min-h-11 items-center justify-center rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">
                    {{ $approveAllLabel }}
                </button>
            </form>
        </div>
    @endif

    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-3 py-2">
                        <label class="inline-flex items-center gap-2 font-medium">
                            <input type="checkbox" id="check-all" @disabled($registrations->isEmpty())>
                            <span class="text-xs">Halaman ini</span>
                        </label>
                    </th>
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

    <form method="POST" action="{{ route('admin.registrations.bulk-approve', $competition) }}" class="mt-3 flex flex-wrap items-center gap-3" id="bulk-approve">
        @csrf
        <div class="bulk-ids"></div>
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800" id="bulk-approve-button" disabled>
            Setujui yang dipilih
        </button>
        <p class="text-xs text-slate-500" id="selected-count">Tidak ada baris dipilih.</p>
    </form>

    <form method="POST" action="{{ route('admin.registrations.bulk-reject', $competition) }}" class="mt-6 max-w-xl space-y-3 rounded-lg border border-slate-200 bg-white p-5" id="bulk-reject">
        @csrf
        <h2 class="font-medium">Tolak yang dipilih</h2>
        <div class="bulk-ids"></div>
        <textarea name="rejection_reason" rows="3" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Alasan penolakan">{{ old('rejection_reason') }}</textarea>
        @error('rejection_reason') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        <button class="rounded-md bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800">Tolak yang dipilih</button>
    </form>

    @include('partials.pagination', ['paginator' => $registrations])

    <script>
        const boxes = [...document.querySelectorAll('.row-check')];
        const checkAll = document.getElementById('check-all');
        const selectedCount = document.getElementById('selected-count');
        const approveButton = document.getElementById('bulk-approve-button');

        function selectedBoxes() {
            return boxes.filter((box) => box.checked);
        }

        function syncSelection() {
            const selected = selectedBoxes();
            if (checkAll) {
                checkAll.checked = boxes.length > 0 && selected.length === boxes.length;
                checkAll.indeterminate = selected.length > 0 && selected.length < boxes.length;
            }
            if (approveButton) {
                approveButton.disabled = selected.length === 0;
            }
            if (selectedCount) {
                selectedCount.textContent = selected.length === 0
                    ? 'Tidak ada baris dipilih.'
                    : selected.length + ' baris di halaman ini dipilih.';
            }
        }

        checkAll?.addEventListener('change', (event) => {
            boxes.forEach((box) => { box.checked = event.target.checked; });
            syncSelection();
        });
        boxes.forEach((box) => box.addEventListener('change', syncSelection));

        function fillIds(form) {
            form.querySelector('.bulk-ids').innerHTML = '';
            selectedBoxes().forEach((box) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'registration_ids[]';
                input.value = box.value;
                form.querySelector('.bulk-ids').appendChild(input);
            });
        }

        function requireSelection(event) {
            if (selectedBoxes().length === 0) {
                event.preventDefault();
                return;
            }
            fillIds(event.target);
        }

        document.getElementById('bulk-approve')?.addEventListener('submit', requireSelection);
        document.getElementById('bulk-reject')?.addEventListener('submit', requireSelection);
        syncSelection();
    </script>
@endsection
