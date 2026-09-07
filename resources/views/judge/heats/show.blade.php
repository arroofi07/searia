@extends('layouts.app')

@section('title', 'Input hasil · Seri '.$heat->heat_number)

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">
                <a href="{{ route('judge.tasks') }}" class="text-teal-800 hover:underline">Tugas juri</a>
            </p>
            <h1 class="text-xl font-semibold sm:text-2xl">
                Acara {{ $heat->event->event_number }} · {{ $heat->event->formattedName() }}
            </h1>
            <p class="mt-1 text-sm text-slate-600">
                {{ $heat->ageGroup?->name }} · Seri {{ $heatIndex }} dari {{ $heatTotal }}
                @if ($locked)
                    <span class="ml-2 rounded bg-teal-100 px-2 py-0.5 text-xs font-medium text-teal-800">Terkunci</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($previous)
                <a href="{{ route('judge.heats.show', $previous) }}" class="min-h-12 rounded-md border border-slate-300 bg-white px-4 py-3 text-sm hover:bg-slate-50">← Seri sebelumnya</a>
            @endif
            @if ($next)
                <a href="{{ route('judge.heats.show', $next) }}" class="min-h-12 rounded-md border border-slate-300 bg-white px-4 py-3 text-sm hover:bg-slate-50">Seri berikutnya →</a>
            @endif
        </div>
    </div>

    @if ($errors->has('lock'))
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $errors->first('lock') }}</div>
    @endif

    <div
        id="heat-result-board"
        class="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white"
        data-fast-input="{{ $fastInput ? '1' : '0' }}"
        data-locked="{{ $locked ? '1' : '0' }}"
    >
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-3 py-3">Lint</th>
                    <th class="px-3 py-3">Nama</th>
                    <th class="px-3 py-3">Klub</th>
                    <th class="px-3 py-3">Seed</th>
                    <th class="px-3 py-3">Hasil</th>
                    <th class="px-3 py-3">Status</th>
                    <th class="px-3 py-3">DSQ</th>
                    <th class="px-3 py-3">Simpan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php
                        $lane = $row['lane'];
                        $result = $lane?->result;
                        $athlete = $lane?->registration?->athlete;
                        $club = $athlete?->club;
                        $empty = $row['empty'];
                    @endphp
                    <tr
                        class="border-t border-slate-100 {{ $empty ? 'bg-slate-50 text-slate-400' : '' }}"
                        data-lane-row
                        @if (! $empty)
                            data-lane-id="{{ $lane->id }}"
                            data-save-url="{{ route('judge.lanes.results.upsert', $lane) }}"
                            data-save-state="{{ $result ? 'saved' : 'idle' }}"
                        @endif
                    >
                        <td class="px-3 py-3 font-medium">{{ $row['lane_number'] }}</td>
                        @if ($empty)
                            <td class="px-3 py-3 italic" colspan="7">kosong</td>
                        @else
                            <td class="px-3 py-3">{{ $athlete?->full_name }}</td>
                            <td class="px-3 py-3">{{ $club?->name }}</td>
                            <td class="px-3 py-3">{{ ($formatTime)($lane->registration?->seed_time_ms) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex min-w-[10rem] flex-col gap-1">
                                    <input
                                        type="text"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        name="time"
                                        value="{{ $result?->time_ms ? ($formatTime)($result->time_ms) : '' }}"
                                        class="min-h-12 w-full rounded-md border border-slate-300 px-3 text-base"
                                        data-time-input
                                        @disabled($locked || ($result && $result->status !== \App\Enums\ResultStatus::Ok))
                                    >
                                    <span class="text-xs text-slate-500" data-time-preview>{{ $result?->time_ms ? ($formatTime)($result->time_ms) : '—' }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <select name="status" class="min-h-12 rounded-md border border-slate-300 px-2 text-base" data-status-input @disabled($locked)>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->value }}" @selected(($result?->status ?? \App\Enums\ResultStatus::Ok) === $status)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-3">
                                <select name="dsq_code" class="min-h-12 rounded-md border border-slate-300 px-2 text-base" data-dsq-input @disabled($locked || ($result?->status !== \App\Enums\ResultStatus::Dsq))>
                                    <option value="">—</option>
                                    @foreach ($dsqCodes as $code)
                                        <option value="{{ $code->value }}" @selected($result?->dsq_code === $code)>{{ $code->value }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-3">
                                <span data-save-indicator class="text-xs text-slate-400">—</span>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex flex-wrap gap-3">
        @unless ($locked)
            <form method="POST" action="{{ route('judge.heats.lock', $heat) }}">
                @csrf
                <button
                    type="submit"
                    class="min-h-12 rounded-md bg-teal-700 px-5 py-3 text-sm font-medium text-white hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50"
                    @disabled(! $canLock)
                    id="lock-heat-button"
                >
                    Kunci seri
                </button>
            </form>
        @endunless
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/heat-results.js'])
@endpush
