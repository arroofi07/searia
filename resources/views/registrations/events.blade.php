@php
    use App\Support\SwimTime;
@endphp

@extends('layouts.app')

@section('title', 'Pilih nomor lomba')

@section('content')
    <h1 class="text-2xl font-semibold">{{ $competition->name }}</h1>
    <p class="mt-1 text-sm text-slate-500">Langkah 2 dari 3 · Pilih nomor lomba dan catatan waktu</p>

    <div class="mt-4 rounded-lg border border-slate-200 bg-white p-4 text-sm">
        <p><strong>Atlet</strong>: {{ $athlete->full_name }}</p>
        <p><strong>Lahir</strong>: {{ $athlete->birth_year }} → {{ $ageGroup->name }}</p>
        <p><strong>Klub</strong>: {{ $athlete->club->name }}{{ $athlete->club->city ? ' - '.$athlete->club->city : '' }}</p>
    </div>

    <form method="POST" action="{{ route('registrations.events.store', $competition) }}" class="mt-6 space-y-4 rounded-lg border border-slate-200 bg-white p-5" id="event-form">
        @csrf
        @error('event_ids') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        @forelse ($events as $event)
            @php
                $checked = in_array($event->id, $wizard['event_ids'] ?? [], false);
                $seed = old('seed_times.'.$event->id, $wizard['seed_times'][$event->id] ?? '');
                $suggested = $suggestions[$event->id] ?? null;
            @endphp
            <div class="flex flex-col gap-2 border-b border-slate-100 py-3 sm:flex-row sm:items-center">
                <label class="flex flex-1 items-center gap-2 text-sm">
                    <input type="checkbox" name="event_ids[]" value="{{ $event->id }}" class="event-box h-4 w-4" @checked($checked)>
                    Acara {{ $event->event_number }} {{ $event->formattedName() }}
                </label>
                <div class="sm:w-56">
                    <input type="text" name="seed_times[{{ $event->id }}]" value="{{ $seed }}"
                        placeholder="{{ $suggested ? SwimTime::formatMilliseconds($suggested) : 'NT' }}"
                        data-suggest="{{ $suggested ? SwimTime::formatMilliseconds($suggested) : '' }}"
                        class="seed-input w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <p class="parsed mt-1 text-xs text-slate-500"></p>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">Tidak ada nomor lomba yang boleh diikuti grup ini.</p>
        @endforelse

        <p class="text-sm font-medium" id="quota">Terpakai {{ $used }} dari {{ $competition->max_events_per_athlete }} nomor yang diizinkan.</p>
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Lanjut ke ringkasan</button>
    </form>

    <script>
        (() => {
            const max = {{ $competition->max_events_per_athlete }};
            const used = {{ $used }};
            const boxes = [...document.querySelectorAll('.event-box')];
            const quota = document.getElementById('quota');
            const parseUrl = @json(route('registrations.parse-time'));
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            function refreshQuota() {
                const selected = boxes.filter((box) => box.checked).length;
                quota.textContent = 'Terpakai ' + (used + selected) + ' dari ' + max + ' nomor yang diizinkan.';
            }

            boxes.forEach((box) => {
                box.addEventListener('change', () => {
                    const selected = boxes.filter((b) => b.checked).length;
                    if (used + selected > max) {
                        box.checked = false;
                        alert('Maksimal ' + max + ' nomor per atlet');
                    }
                    const input = box.closest('div').querySelector('.seed-input');
                    if (box.checked && input && !input.value && input.dataset.suggest) {
                        input.value = input.dataset.suggest;
                        preview(input);
                    }
                    refreshQuota();
                });
            });

            document.querySelectorAll('.seed-input').forEach((input) => {
                input.addEventListener('blur', () => preview(input));
            });

            async function preview(input) {
                const target = input.parentElement.querySelector('.parsed');
                const response = await fetch(parseUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ input: input.value }),
                });
                const payload = await response.json();
                target.textContent = payload.ok ? payload.formatted : (payload.message || '');
            }

            refreshQuota();
        })();
    </script>
@endsection
