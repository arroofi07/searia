@php
    use App\Support\SwimTime;
@endphp

@extends('layouts.public')

@section('title', 'Pilih nomor lomba')
@section('meta_description', 'Pilih nomor lomba dan isi catatan waktu untuk '.$competition->name)

@section('content')
    <div class="mx-auto max-w-3xl pb-36 sm:pb-0">
        <p class="text-sm font-semibold uppercase tracking-wide text-teal-800">Pendaftaran</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $competition->name }}</h1>
        <p class="mt-1 text-sm leading-6 text-slate-600">
            Centang nomor yang diikuti. Hanya nomor yang cocok dengan jenis kelamin dan kelompok umur atlet yang tampil di sini.
        </p>

        @include('register._steps', ['current' => 2])

        <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow-sm">
            <dl class="grid gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Atlet</dt>
                    <dd class="mt-0.5 font-medium">{{ $athlete->full_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kelompok umur</dt>
                    <dd class="mt-0.5 font-medium">Lahir {{ $athlete->birth_year }} → {{ $ageGroup->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Klub</dt>
                    <dd class="mt-0.5 font-medium">{{ $athlete->club->name }}{{ $athlete->club->city ? ' · '.$athlete->club->city : '' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori</dt>
                    <dd class="mt-0.5 font-medium">{{ $athlete->gender->eventGender()->label() }}</dd>
                </div>
            </dl>
            <a href="{{ route('register.create', $competition) }}" class="mt-3 inline-block min-h-11 py-2 text-sm font-medium text-teal-800 hover:underline">Ubah data atlet</a>
        </div>

        <div class="mt-5">
            @include('register._seed-guide')
        </div>

        <form method="POST" action="{{ route('register.events.store', $competition) }}" class="mt-6" id="event-form"
            data-max-events="{{ $competition->max_events_per_athlete }}"
            data-used-events="{{ $used }}">
            @csrf
            @error('event_ids') <p class="mb-3 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p> @enderror

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Nomor yang boleh diikuti {{ $ageGroup->name }}</h2>
                <p class="mt-1 text-sm text-slate-600">Maksimal {{ $competition->max_events_per_athlete }} nomor per atlet di kejuaraan ini.</p>

                <ul class="mt-4 space-y-3">
                    @forelse ($events as $event)
                        @php
                            $checked = in_array($event->id, old('event_ids', $state['event_ids'] ?? []), false);
                            $seed = old('seed_times.'.$event->id, $state['seed_times'][$event->id] ?? '');
                            $suggested = $suggestions[$event->id] ?? null;
                        @endphp
                        <li data-event-card class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 {{ $checked ? 'selected' : '' }}">
                            <label for="event-{{ $event->id }}" class="flex cursor-pointer items-start gap-3">
                                <input id="event-{{ $event->id }}" type="checkbox" name="event_ids[]" value="{{ $event->id }}"
                                    class="event-box mt-1 h-6 w-6 shrink-0 rounded-md border-slate-300 text-teal-700 focus:ring-teal-600"
                                    @checked($checked)>
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                        <span class="font-mono text-lg font-bold text-teal-800">{{ $event->paddedEventNumber() }}</span>
                                        <span class="text-base font-semibold leading-snug text-slate-900">{{ $event->programName() }}</span>
                                    </span>
                                    <span class="mt-1 block text-sm text-slate-500">
                                        {{ $athlete->gender->eventGender()->label() }}
                                        @if ($event->equipment !== \App\Enums\Equipment::None)
                                            · Alat: {{ $event->equipment->label() }}
                                        @endif
                                    </span>
                                </span>
                            </label>

                            <div data-seed-field class="mt-4 border-t border-teal-100 pt-3" @if (! $checked) hidden @endif>
                                <label for="seed-{{ $event->id }}" class="block text-sm font-medium text-slate-800">Catatan waktu nomor ini</label>
                                <p class="mt-0.5 text-xs text-slate-500">Ketik 6 angka. Paling kiri = menit. Kosongkan jika belum punya waktu.</p>
                                <input id="seed-{{ $event->id }}" type="text" name="seed_times[{{ $event->id }}]" value="{{ $seed }}"
                                    inputmode="numeric" autocomplete="off" maxlength="20" data-seed-input
                                    placeholder="{{ $suggested ? SwimTime::formatMilliseconds($suggested) : 'Contoh: 013470' }}"
                                    data-suggest="{{ $suggested ? SwimTime::formatMilliseconds($suggested) : '' }}"
                                    class="seed-input public-input font-mono tracking-wide"
                                    @disabled(! $checked)>

                                <div data-seed-boxes class="mt-3 flex items-center justify-start gap-1" aria-hidden="true">
                                    <span data-seed-digit class="seed-digit">·</span>
                                    <span data-seed-digit class="seed-digit">·</span>
                                    <span class="px-0.5 font-mono text-slate-400">:</span>
                                    <span data-seed-digit class="seed-digit">·</span>
                                    <span data-seed-digit class="seed-digit">·</span>
                                    <span class="px-0.5 font-mono text-slate-400">.</span>
                                    <span data-seed-digit class="seed-digit">·</span>
                                    <span data-seed-digit class="seed-digit">·</span>
                                </div>
                                <p data-seed-preview class="parsed mt-2 text-sm leading-5 text-slate-600"></p>
                                @if ($suggested)
                                    <p class="mt-1 text-xs text-teal-800">Waktu dari lomba sebelumnya sudah diisi. Boleh diubah jika ada yang lebih baru.</p>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="rounded-2xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">
                            Tidak ada nomor lomba yang boleh diikuti kelompok umur ini.
                        </li>
                    @endforelse
                </ul>
            </div>

            <div class="mt-4 hidden items-center justify-between gap-3 sm:flex">
                <p class="text-sm font-medium text-slate-700" id="quota">
                    Terpakai {{ $used }} dari {{ $competition->max_events_per_athlete }} nomor yang diizinkan.
                </p>
                <button class="public-btn">Lanjut ke ringkasan</button>
            </div>

            <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-8px_24px_rgba(15,23,42,0.08)] sm:hidden">
                <p class="mb-2 text-center text-xs font-medium text-slate-600" id="quota-bar">
                    Terpakai {{ $used }} dari {{ $competition->max_events_per_athlete }} nomor yang diizinkan.
                </p>
                <button class="public-btn">Lanjut ke ringkasan</button>
            </div>
        </form>
    </div>
@endsection
