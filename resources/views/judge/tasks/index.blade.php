@extends('layouts.app')

@section('title', 'Tugas juri')

@section('content')
    <div class="flex items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Tugas saya</h1>
            <p class="mt-1 text-sm text-slate-500">Nomor lomba yang ditugaskan. Halaman disegarkan otomatis.</p>
        </div>
        @php
            $firstCompetition = $tasks->first()['event']->competition ?? null;
        @endphp
        @if ($firstCompetition)
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('start-list.pdf', $firstCompetition) }}" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50">PDF acara</a>
                <a href="{{ route('results.pdf', $firstCompetition) }}" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50">PDF hasil</a>
            </div>
        @endif
    </div>

    @if ($empty)
        <div class="mt-8 rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-600">
            Belum ada nomor lomba yang ditugaskan kepada Anda. Hubungi panitia untuk penugasan.
        </div>
    @else
        <div class="mt-6 space-y-4">
            @foreach ($tasks as $task)
                @php
                    $event = $task['event'];
                @endphp
                <section class="rounded-lg border border-slate-200 bg-white">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                        <div>
                            <h2 class="font-medium">Acara {{ $event->event_number }} · {{ $event->formattedName() }}</h2>
                            <p class="text-xs text-slate-500">{{ $event->competition?->name }}</p>
                        </div>
                        <p class="text-sm {{ $task['complete'] ? 'text-teal-700' : 'text-slate-600' }}">
                            {{ $task['locked'] }} / {{ $task['total'] }} seri dikunci
                        </p>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($task['heats'] as $heat)
                            <li>
                                <a href="{{ route('judge.heats.show', $heat) }}"
                                    class="flex min-h-14 items-center justify-between gap-3 px-4 py-3 text-sm hover:bg-slate-50">
                                    <span>
                                        {{ $heat->ageGroup?->name }} · Seri {{ $heat->heat_number }}
                                    </span>
                                    @if ($heat->isResultsLocked())
                                        <span class="rounded bg-teal-100 px-2 py-1 text-xs font-medium text-teal-800">Terkunci</span>
                                    @else
                                        <span class="rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-900">Belum dikunci</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        setTimeout(() => window.location.reload(), 30000);
    </script>
@endpush
