@extends('layouts.public')

@section('title', $table->eventTitle)
@section('meta_description', $table->eventTitle.' · '.$table->ageGroupName.' · '.$competition->name)
@section('og_title', $table->eventTitle)

@section('content')
    @include('public._mast', [
        'back' => route('results.index', $competition),
        'backLabel' => 'Semua hasil',
        'kicker' => $table->ageGroupName,
        'title' => $table->eventTitle,
    ])
    @if ($competition->hasPublicResults() && $competition->status !== \App\Enums\CompetitionStatus::Published)
        <div class="note">Hasil sementara — lomba belum dipublikasikan.</div>
    @elseif ($preview)
        <div class="note">Pratinjau panitia</div>
    @endif
    <div class="program">
        @include('results._table', ['table' => $table, 'entries' => $entries, 'formatTime' => $formatTime, 'competition' => $competition])
    </div>
    @include('partials.pagination', ['paginator' => $entries])

    @php
        $entryNames = collect($table->entries)->keyBy('resultId');
    @endphp
    @if ($correctionLogs->total() > 0)
        <section class="mt-8">
            <h2 class="text-lg font-semibold">Riwayat koreksi</h2>
            <p class="mt-1 text-sm text-slate-500">Perubahan hasil setelah pencatatan, termasuk yang dilakukan setelah publikasi.</p>
            <ul class="mt-4 space-y-3">
                @foreach ($correctionLogs as $log)
                    @php
                        $entry = $entryNames->get($log->subject_id);
                        $old = $log->old_values ?? [];
                        $new = $log->new_values ?? [];
                    @endphp
                    <li class="panel">
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
            @include('partials.pagination', ['paginator' => $correctionLogs])
        </section>
    @endif
@endsection
