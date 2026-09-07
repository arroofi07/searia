@extends('layouts.app')

@section('title', 'Hasil · Acara '.$event->event_number)

@section('content')
    <h1 class="text-2xl font-semibold">Hasil acara {{ $event->event_number }}</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }} · {{ $event->formattedName() }}</p>
    <p class="mt-2 text-sm">
        <a href="{{ route('admin.activity-logs.subject') }}?{{ http_build_query(['type' => \App\Models\Event::class, 'id' => $event->id]) }}" class="text-teal-800 hover:underline">Riwayat audit acara</a>
    </p>

    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'results'])

    @if ($publishedWarning)
        <div class="mt-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            Kejuaraan sudah berstatus <strong>published</strong>. Koreksi akan terlihat oleh publik dan wajib disertai konfirmasi.
        </div>
    @endif

    <div class="mt-6 space-y-6">
        @foreach ($heats as $heat)
            <section class="rounded-lg border border-slate-200 bg-white">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                    <h2 class="font-medium">{{ $heat->ageGroup?->name }} · Seri {{ $heat->heat_number }}</h2>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($heat->isResultsLocked())
                            <span class="rounded bg-teal-100 px-2 py-1 text-xs font-medium text-teal-800">Hasil terkunci</span>
                            <form method="POST" action="{{ route('admin.heats.unlock', $heat) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                <input type="text" name="reason" required placeholder="Alasan buka kunci" class="rounded-md border border-slate-300 px-2 py-1 text-sm">
                                <button class="rounded-md border border-slate-300 px-3 py-1 text-sm hover:bg-slate-50">Buka kunci</button>
                            </form>
                        @endif
                        <a href="{{ route('judge.heats.show', $heat) }}" class="text-sm text-teal-800 hover:underline">Buka layar input</a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Lint</th>
                                <th class="px-3 py-2">Nama</th>
                                <th class="px-3 py-2">Hasil</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Koreksi</th>
                                <th class="px-3 py-2">Riwayat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($heat->lanes->sortBy('lane_number') as $lane)
                                @php $result = $lane->result; @endphp
                                <tr class="border-t border-slate-100 align-top">
                                    <td class="px-3 py-3">{{ $lane->lane_number }}</td>
                                    <td class="px-3 py-3">
                                        {{ $lane->registration?->athlete?->full_name ?? '—' }}
                                        <div class="text-xs text-slate-500">{{ $lane->registration?->athlete?->club?->name }}</div>
                                    </td>
                                    <td class="px-3 py-3">{{ $result ? ($formatTime)($result->time_ms) : '—' }}</td>
                                    <td class="px-3 py-3">
                                        {{ $result?->status?->label() ?? '—' }}
                                        @if ($result?->dsq_code)
                                            <span class="text-xs text-slate-500">({{ $result->dsq_code->value }})</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        @if ($result)
                                            <form method="POST" action="{{ route('admin.results.correct', $result) }}" class="space-y-2">
                                                @csrf
                                                @method('PUT')
                                                <input type="text" name="time" value="{{ $result->time_ms ? ($formatTime)($result->time_ms) : '' }}" placeholder="Waktu" class="w-full rounded-md border border-slate-300 px-2 py-1.5">
                                                <select name="status" class="w-full rounded-md border border-slate-300 px-2 py-1.5">
                                                    @foreach ($statuses as $status)
                                                        <option value="{{ $status->value }}" @selected($result->status === $status)>{{ $status->label() }}</option>
                                                    @endforeach
                                                </select>
                                                <select name="dsq_code" class="w-full rounded-md border border-slate-300 px-2 py-1.5">
                                                    <option value="">Kode DSQ</option>
                                                    @foreach ($dsqCodes as $code)
                                                        <option value="{{ $code->value }}" @selected($result->dsq_code === $code)>{{ $code->value }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="text" name="reason" required placeholder="Alasan koreksi" class="w-full rounded-md border border-slate-300 px-2 py-1.5">
                                                @if ($publishedWarning)
                                                    <label class="flex items-center gap-2 text-xs text-amber-900">
                                                        <input type="checkbox" name="confirm_published" value="1">
                                                        Saya paham hasil sudah dipublikasikan
                                                    </label>
                                                @endif
                                                <button class="rounded-md bg-slate-800 px-3 py-1.5 text-xs font-medium text-white">Koreksi</button>
                                            </form>
                                        @else
                                            <span class="text-slate-400">Belum ada hasil</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-xs text-slate-600">
                                        @forelse ($result?->activityLogs?->where('action', 'result.correct') ?? [] as $log)
                                            @php
                                                $old = $log->old_values ?? [];
                                                $new = $log->new_values ?? [];
                                            @endphp
                                            <div class="mb-2 rounded border border-slate-100 p-2">
                                                <div>{{ $log->user?->name }} · {{ $log->created_at?->format('d/m H:i') }}</div>
                                                <div>
                                                    {{ $old['status'] ?? '—' }}
                                                    @if (($old['status'] ?? null) === 'ok') {{ ($formatTime)($old['time_ms'] ?? null) }} @endif
                                                    →
                                                    {{ $new['status'] ?? '—' }}
                                                    @if (($new['status'] ?? null) === 'ok') {{ ($formatTime)($new['time_ms'] ?? null) }} @endif
                                                </div>
                                                <div>Alasan: {{ $log->reason }}</div>
                                            </div>
                                        @empty
                                            <span class="text-slate-400">—</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>
@endsection
