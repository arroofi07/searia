@php
    use App\Support\SwimTime;
@endphp

@extends('layouts.public')

@section('title', 'Pilih nomor lomba')

@section('content')
    <div class="mx-auto max-w-3xl">
        <p class="text-sm font-medium uppercase tracking-wide text-teal-800">Pendaftaran · Langkah 2 dari 3</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $competition->name }}</h1>
        <p class="mt-1 text-sm text-slate-500">Pilih nomor lomba sesuai susunan acara. Hanya nomor yang cocok dengan gender dan kelompok umur atlet yang ditampilkan.</p>

        <div class="mt-5 rounded-xl border border-slate-200 bg-white p-4 text-sm shadow-sm">
            <dl class="grid gap-2 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Atlet</dt>
                    <dd class="font-medium">{{ $athlete->full_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Kelompok umur</dt>
                    <dd class="font-medium">{{ $athlete->birth_year }} → {{ $ageGroup->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Klub</dt>
                    <dd class="font-medium">{{ $athlete->club->name }}{{ $athlete->club->city ? ' · '.$athlete->club->city : '' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Kategori</dt>
                    <dd class="font-medium">{{ $athlete->gender->eventGender()->label() }} ({{ $athlete->gender->eventGender()->value }})</dd>
                </div>
            </dl>
            <a href="{{ route('register.create', $competition) }}" class="mt-3 inline-block text-sm text-teal-800 hover:underline">Ubah data atlet</a>
        </div>

        <form method="POST" action="{{ route('register.events.store', $competition) }}" class="mt-6" id="event-form">
            @csrf
            @error('event_ids') <p class="mb-3 text-sm text-red-600">{{ $message }}</p> @enderror

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-900 px-4 py-3 text-center">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-teal-300">Susunan Acara Perlombaan</p>
                    <p class="mt-1 text-sm text-white">Nomor yang boleh diikuti {{ $ageGroup->name }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-teal-800 text-left text-xs font-semibold uppercase tracking-wide text-white">
                                <th class="w-16 px-3 py-3 text-center">{{ $athlete->gender->eventGender()->value }}</th>
                                <th class="px-3 py-3">Nomor Perlombaan</th>
                                <th class="w-20 px-3 py-3 text-center">Pilih</th>
                                <th class="min-w-[10rem] px-3 py-3">Seed time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($events as $event)
                                @php
                                    $checked = in_array($event->id, old('event_ids', $state['event_ids'] ?? []), false);
                                    $seed = old('seed_times.'.$event->id, $state['seed_times'][$event->id] ?? '');
                                    $suggested = $suggestions[$event->id] ?? null;
                                @endphp
                                <tr class="event-row border-t border-slate-100 {{ $checked ? 'bg-teal-50/60' : 'bg-white' }} hover:bg-slate-50">
                                    <td class="px-3 py-3 text-center font-mono text-base font-semibold text-teal-900">
                                        {{ $event->paddedEventNumber() }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <label for="event-{{ $event->id }}" class="cursor-pointer font-medium tracking-wide text-slate-900">
                                            {{ $event->programName() }}
                                        </label>
                                        @if ($event->equipment !== \App\Enums\Equipment::None)
                                            <span class="mt-0.5 block text-xs text-slate-500">Alat: {{ $event->equipment->label() }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <input id="event-{{ $event->id }}" type="checkbox" name="event_ids[]" value="{{ $event->id }}"
                                            class="event-box h-5 w-5 rounded border-slate-300 text-teal-700 focus:ring-teal-600"
                                            @checked($checked)>
                                    </td>
                                    <td class="px-3 py-3">
                                        <input type="text" name="seed_times[{{ $event->id }}]" value="{{ $seed }}"
                                            placeholder="{{ $suggested ? SwimTime::formatMilliseconds($suggested) : 'NT / kosong' }}"
                                            data-suggest="{{ $suggested ? SwimTime::formatMilliseconds($suggested) : '' }}"
                                            class="seed-input w-full rounded-md border border-slate-300 px-2.5 py-1.5 font-mono text-sm disabled:bg-slate-100"
                                            @disabled(! $checked)>
                                        <p class="parsed mt-1 text-xs text-slate-500"></p>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-slate-500">
                                        Tidak ada nomor lomba yang boleh diikuti kelompok umur ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-medium text-slate-700" id="quota">
                    Terpakai {{ $used }} dari {{ $competition->max_events_per_athlete }} nomor yang diizinkan.
                </p>
                <button class="rounded-md bg-teal-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-teal-800">
                    Lanjut ke ringkasan
                </button>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const max = {{ $competition->max_events_per_athlete }};
            const used = {{ $used }};
            const boxes = [...document.querySelectorAll('.event-box')];
            const quota = document.getElementById('quota');
            const parseUrl = @json(route('register.parse-time'));
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            function syncRow(box) {
                const row = box.closest('tr');
                const input = row.querySelector('.seed-input');
                row.classList.toggle('bg-teal-50/60', box.checked);
                if (input) {
                    input.disabled = !box.checked;
                    if (!box.checked) {
                        input.value = '';
                        const parsed = row.querySelector('.parsed');
                        if (parsed) parsed.textContent = '';
                    } else if (!input.value && input.dataset.suggest) {
                        input.value = input.dataset.suggest;
                        preview(input);
                    }
                }
            }

            function refreshQuota() {
                const selected = boxes.filter((box) => box.checked).length;
                quota.textContent = 'Terpakai ' + (used + selected) + ' dari ' + max + ' nomor yang diizinkan.';
            }

            boxes.forEach((box) => {
                syncRow(box);
                box.addEventListener('change', () => {
                    const selected = boxes.filter((b) => b.checked).length;
                    if (used + selected > max) {
                        box.checked = false;
                        alert('Maksimal ' + max + ' nomor per atlet');
                    }
                    syncRow(box);
                    refreshQuota();
                });
            });

            document.querySelectorAll('.seed-input').forEach((input) => {
                input.addEventListener('blur', () => preview(input));
            });

            async function preview(input) {
                const target = input.parentElement.querySelector('.parsed');
                if (!target) return;
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
