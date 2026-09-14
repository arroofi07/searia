@php
    use App\Enums\ResultStatus;
@endphp

@extends('layouts.app')

@section('title', 'Input hasil · Seri '.$heat->heat_number)

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">
                <a href="{{ route('judge.tasks') }}" class="text-teal-800 hover:underline">Tugas juri</a>
            </p>
            <h1 class="text-xl font-semibold sm:text-2xl">
                <span class="font-mono text-teal-800">{{ $heat->event->paddedEventNumber() }}</span>
                {{ $heat->event->formattedName() }}
            </h1>
            <p class="mt-1 text-sm text-slate-600">
                {{ $heat->event->competition?->name }}
                · {{ $heat->ageGroup?->name }}
                · Seri {{ $heatIndex }} dari {{ $heatTotal }}
            </p>
            <div class="mt-2 flex flex-wrap gap-2">
                @if ($locked)
                    <span class="rounded-full bg-teal-100 px-2.5 py-1 text-xs font-medium text-teal-800">Terkunci</span>
                @else
                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-900">Belum dikunci</span>
                @endif
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $recordedCount }}/{{ $occupiedCount }} lintasan tercatat</span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($previous)
                <a href="{{ route('judge.heats.show', $previous) }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">← Seri sebelumnya</a>
            @endif
            @if ($next)
                <a href="{{ route('judge.heats.show', $next) }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Seri berikutnya →</a>
            @endif
        </div>
    </div>

    @if ($siblings->count() > 1)
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($siblings as $sibling)
                <a
                    href="{{ route('judge.heats.show', $sibling) }}"
                    class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-md px-3 text-sm font-medium {{ $sibling->id === $heat->id ? 'bg-teal-700 text-white' : 'border border-slate-300 bg-white hover:bg-slate-50' }}"
                >
                    {{ $sibling->heat_number }}
                </a>
            @endforeach
        </div>
    @endif

    @error('lock')
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $message }}</div>
    @enderror

    @if ($locked)
        <div class="mt-4 rounded-2xl border border-teal-200 bg-teal-50 px-5 py-4 text-sm leading-6 text-teal-950">
            Seri ini terkunci. Juri tidak bisa mengubah hasil. Hubungi panitia jika perlu dibuka.
        </div>
    @endif

    <details class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 text-sm leading-6 text-slate-700">
        <summary class="cursor-pointer font-semibold text-slate-900">Arti status dan kode DSQ</summary>
        <div class="mt-3 grid gap-4 border-t border-slate-100 pt-3 sm:grid-cols-2">
            <div>
                <p class="font-medium text-slate-900">Status lintasan</p>
                <ul class="mt-2 space-y-1.5">
                    @foreach ($statuses as $status)
                        <li><span class="font-mono font-semibold">{{ $status->label() }}</span> — {{ $status->description() }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="font-medium text-slate-900">Kode DSQ (hanya jika status DSQ)</p>
                <ul class="mt-2 space-y-1.5">
                    @foreach ($dsqCodes as $code)
                        <li><span class="font-mono font-semibold">{{ $code->value }}</span> — {{ $code->description() }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @if ($fastInput)
            <p class="mt-3 text-slate-600">Ketik waktu cepat, contoh <strong>3210</strong> menjadi <strong>00:32.10</strong>. Enter pindah ke lintasan berikutnya.</p>
        @endif
    </details>

    <div
        id="heat-result-board"
        class="mt-5 space-y-3"
        data-fast-input="{{ $fastInput ? '1' : '0' }}"
        data-locked="{{ $locked ? '1' : '0' }}"
    >
        @foreach ($rows as $row)
            @php
                $lane = $row['lane'];
                $result = $lane?->result;
                $athlete = $lane?->registration?->athlete;
                $club = $athlete?->club;
                $empty = $row['empty'];
                $statusValue = $result?->status ?? ResultStatus::Ok;
            @endphp

            @if ($empty)
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                    Lintasan {{ $row['lane_number'] }} kosong
                </div>
            @else
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-4"
                    data-lane-row
                    data-lane-id="{{ $lane->id }}"
                    data-save-url="{{ route('judge.lanes.results.upsert', $lane) }}"
                    data-save-state="{{ $result ? 'saved' : 'idle' }}"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Lintasan {{ $row['lane_number'] }}</p>
                            <h2 class="mt-0.5 text-lg font-semibold text-slate-900">{{ $athlete?->full_name }}</h2>
                            <p class="text-sm text-slate-500">{{ $club?->name ?? 'Tanpa klub' }} · Seed {{ ($formatTime)($lane->registration?->seed_time_ms) }}</p>
                        </div>
                        <span data-save-indicator class="shrink-0 text-xs text-slate-400">—</span>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div data-time-wrap>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="time-{{ $lane->id }}">Waktu</label>
                            <input
                                id="time-{{ $lane->id }}"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                name="time"
                                value="{{ $result?->time_ms ? ($formatTime)($result->time_ms) : '' }}"
                                placeholder="{{ $fastInput ? '3210' : '00:32.10' }}"
                                class="mt-1 min-h-12 w-full rounded-md border border-slate-300 px-3 font-mono text-lg"
                                data-time-input
                                @disabled($locked || ($result && $result->status !== ResultStatus::Ok))
                            >
                            <p class="mt-1 text-xs text-slate-500" data-time-preview>{{ $result?->time_ms ? ($formatTime)($result->time_ms) : '—' }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="status-{{ $lane->id }}">Status</label>
                            <select
                                id="status-{{ $lane->id }}"
                                name="status"
                                class="mt-1 min-h-12 w-full rounded-md border border-slate-300 px-3 text-base"
                                data-status-input
                                @disabled($locked)
                            >
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected($statusValue === $status)>{{ $status->optionLabel() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2 {{ $statusValue === ResultStatus::Dsq ? '' : 'hidden' }}" data-dsq-wrap>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="dsq-{{ $lane->id }}">Alasan DSQ</label>
                            <select
                                id="dsq-{{ $lane->id }}"
                                name="dsq_code"
                                class="mt-1 min-h-12 w-full rounded-md border border-slate-300 px-3 text-base"
                                data-dsq-input
                                @disabled($locked || $statusValue !== ResultStatus::Dsq)
                            >
                                <option value="">Pilih kode diskualifikasi</option>
                                @foreach ($dsqCodes as $code)
                                    <option value="{{ $code->value }}" @selected($result?->dsq_code === $code)>{{ $code->optionLabel() }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Wajib diisi jika status DSQ. Kode resmi tetap SF, ST, TN, FN, NA, atau OT.</p>
                        </div>
                    </div>
                </article>
            @endif
        @endforeach
    </div>

    <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-5">
        @unless ($locked)
            <p class="text-sm text-slate-600" data-lock-hint>
                @if ($canLock)
                    Semua lintasan terisi sudah tercatat. Kunci seri jika yakin hasil benar.
                @else
                    Tombol kunci aktif setelah semua lintasan terisi punya hasil (waktu atau DNS/DNF/DSQ).
                @endif
            </p>
            <form method="POST" action="{{ route('judge.heats.lock', $heat) }}" class="mt-3">
                @csrf
                <button
                    type="submit"
                    class="inline-flex min-h-12 items-center justify-center rounded-md bg-teal-700 px-5 py-3 text-sm font-medium text-white hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50"
                    @disabled(! $canLock)
                    id="lock-heat-button"
                >
                    Kunci seri
                </button>
            </form>
        @else
            <p class="text-sm text-slate-600">Hasil seri ini sudah dikunci.</p>
        @endunless
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/heat-results.js'])
@endpush
