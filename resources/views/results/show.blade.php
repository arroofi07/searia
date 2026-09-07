@extends('layouts.public')

@section('title', $table->eventTitle)
@section('meta_description', $table->eventTitle.' · '.$table->ageGroupName.' · '.$competition->name)
@section('og_title', $table->eventTitle)

@section('content')
    <p class="text-sm text-slate-500"><a href="{{ route('results.index', $competition) }}" class="text-teal-800 hover:underline">← Semua hasil</a></p>
    <h1 class="mt-2 text-2xl font-semibold">{{ $table->eventTitle }}</h1>
    <p class="text-sm text-slate-500">{{ $table->ageGroupName }}</p>
    @if ($preview)
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">Pratinjau panitia</div>
    @endif
    <div class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white">
        @include('results._table', ['table' => $table, 'formatTime' => $formatTime, 'competition' => $competition])
    </div>

    @php
        $flatCorrections = collect($corrections ?? [])->flatten(1);
        $entryNames = collect($table->entries)->keyBy('resultId');
    @endphp
    @if ($flatCorrections->isNotEmpty())
        <section class="mt-8">
            <h2 class="text-lg font-semibold">Riwayat koreksi</h2>
            <p class="mt-1 text-sm text-slate-500">Perubahan hasil setelah pencatatan, termasuk yang dilakukan setelah publikasi.</p>
            <ul class="mt-4 space-y-3">
                @foreach ($flatCorrections as $log)
                    @php
                        $entry = $entryNames->get($log->subject_id);
                        $old = $log->old_values ?? [];
                        $new = $log->new_values ?? [];
                    @endphp
                    <li class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm">
                        <div class="font-medium">{{ $entry?->athleteName ?? 'Hasil #'.$log->subject_id }}</div>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ $log->user?->name ?? 'Panitia' }} · {{ $log->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                        </div>
                        <div class="mt-2 text-slate-700">
                            {{ ($old['status'] ?? '—') }}
                            @if (($old['status'] ?? null) === 'ok')
                                {{ ($formatTime)($old['time_ms'] ?? null) }}
                            @elseif (!empty($old['dsq_code']))
                                ({{ $old['dsq_code'] }})
                            @endif
                            →
                            {{ ($new['status'] ?? '—') }}
                            @if (($new['status'] ?? null) === 'ok')
                                {{ ($formatTime)($new['time_ms'] ?? null) }}
                            @elseif (!empty($new['dsq_code']))
                                ({{ $new['dsq_code'] }})
                            @endif
                        </div>
                        @if ($log->reason)
                            <p class="mt-1 text-slate-600">Alasan: {{ $log->reason }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
