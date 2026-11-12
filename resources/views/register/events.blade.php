@php
    use App\Support\SwimTime;
@endphp

@extends('layouts.public')

@section('title', 'Pilih kode acara')
@section('meta_description', 'Pilih kode acara dan isi catatan waktu untuk '.$competition->name)

@section('content')
    <div class="pb-dock">
        @include('public._mast', [
            'competition' => $competition,
            'kicker' => 'Pendaftaran · langkah 2',
            'title' => $competition->name,
            'lead' => 'Centang kode acara yang diikuti. Hanya nomor yang cocok dengan L/P dan kelompok umur peserta yang tampil di sini.',
        ])

        @include('register._steps', ['current' => 2])

        <dl class="facts">
                <div>
                    <dt>Nama lengkap</dt>
                    <dd>{{ $athlete->full_name }}</dd>
                </div>
                <div>
                    <dt>L/P · Tahun lahir</dt>
                    <dd>{{ $athlete->gender->value }} · {{ $athlete->birth_year }} → {{ $ageGroup->name }}</dd>
                </div>
                <div>
                    <dt>Klub/sekolah</dt>
                    <dd>{{ $athlete->club->name }}</dd>
                </div>
                <div>
                    <dt>Kabupaten/kota</dt>
                    <dd>{{ $athlete->club->city ?: '—' }}</dd>
                </div>
            </dl>
            <a class="back" href="{{ route('register.create', $competition) }}">Ubah data peserta</a>

        <div class="gap-top">
            @include('register._seed-guide')
        </div>

        <form method="POST" action="{{ route('register.events.store', $competition) }}" class="mt-6" id="event-form"
            data-max-events="{{ $competition->max_events_per_athlete }}"
            data-used-events="{{ $used }}">
            @csrf
            @error('event_ids') <p class="note">{{ $message }}</p> @enderror

            <div class="panel">
                <h2>Kode acara untuk {{ $ageGroup->name }}</h2>
                <p class="lead">Maksimal {{ $competition->max_events_per_athlete }} kode acara per peserta di kejuaraan ini.</p>

                <ul class="mt-4 space-y-3">
                    @forelse ($events as $event)
                        @php
                            $checked = in_array($event->id, old('event_ids', $state['event_ids'] ?? []), false);
                            $seed = old('seed_times.'.$event->id, $state['seed_times'][$event->id] ?? '');
                            $suggested = $suggestions[$event->id] ?? null;
                        @endphp
                        <li data-event-card class="panel {{ $checked ? 'selected' : '' }}">
                            <label for="event-{{ $event->id }}" class="pick">
                                <input id="event-{{ $event->id }}" type="checkbox" name="event_ids[]" value="{{ $event->id }}"
                                    class="event-box"
                                    @checked($checked)>
                                <span>
                                    <span class="pick-title">
                                        <span class="mono"><span class="sr-only">Kode acara </span>{{ $event->paddedEventNumber() }}</span>
                                        <strong>{{ $event->programName() }}</strong>
                                    </span>
                                    <span class="meta">
                                        {{ $athlete->gender->eventGender()->label() }}
                                        @if ($event->equipment !== \App\Enums\Equipment::None)
                                            · Alat: {{ $event->equipment->label() }}
                                        @endif
                                    </span>
                                </span>
                            </label>

                            <div data-seed-field class="gap-top" @if (! $checked) hidden @endif>
                                <label for="seed-{{ $event->id }}">Catatan waktu</label>
                                <p class="meta">Ketik 6 angka. Paling kiri = menit. Kosongkan jika belum punya waktu.</p>
                                <input id="seed-{{ $event->id }}" type="text" name="seed_times[{{ $event->id }}]" value="{{ $seed }}"
                                    inputmode="numeric" autocomplete="off" maxlength="20" data-seed-input
                                    placeholder="{{ $suggested ? SwimTime::formatMilliseconds($suggested) : 'Contoh: 013470' }}"
                                    data-suggest="{{ $suggested ? SwimTime::formatMilliseconds($suggested) : '' }}"
                                    class="seed-input public-input font-mono tracking-wide"
                                    @disabled(! $checked)>

                                <div data-seed-boxes class="digits" aria-hidden="true">
                                    <span data-seed-digit class="seed-digit">·</span>
                                    <span data-seed-digit class="seed-digit">·</span>
                                    <span class="px-0.5 font-mono text-slate-400">:</span>
                                    <span data-seed-digit class="seed-digit">·</span>
                                    <span data-seed-digit class="seed-digit">·</span>
                                    <span class="px-0.5 font-mono text-slate-400">.</span>
                                    <span data-seed-digit class="seed-digit">·</span>
                                    <span data-seed-digit class="seed-digit">·</span>
                                </div>
                                <p data-seed-preview class="parsed meta"></p>
                                @if ($suggested)
                                    <p class="meta">Waktu dari lomba sebelumnya sudah diisi. Boleh diubah jika ada yang lebih baru.</p>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="note">
                            Tidak ada kode acara yang boleh diikuti kelompok umur ini.
                        </li>
                    @endforelse
                </ul>
            </div>

            <div class="toolbar gap-top desk-only">
                <p id="quota">
                    Terpakai {{ $used }} dari {{ $competition->max_events_per_athlete }} kode acara yang diizinkan.
                </p>
                <button class="public-btn">Lanjut ke ringkasan</button>
            </div>

            <div class="dock">
                <p id="quota-bar">
                    Terpakai {{ $used }} dari {{ $competition->max_events_per_athlete }} kode acara yang diizinkan.
                </p>
                <button class="public-btn">Lanjut ke ringkasan</button>
            </div>
        </form>
    </div>
@endsection
