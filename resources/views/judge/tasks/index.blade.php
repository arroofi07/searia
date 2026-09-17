@php
    $statusFilter = (string) ($filters['status'] ?? '');
    $progress = $heatTotal > 0 ? (int) round(($lockedCount / $heatTotal) * 100) : 0;
@endphp

@extends('layouts.app')

@section('title', 'Tugas juri')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Tugas juri</h1>
            <p class="mt-1 text-sm text-slate-600">
                @if ($manages)
                    Semua nomor yang sudah punya seri. Juri hanya melihat nomor yang ditugaskan.
                @else
                    Nomor lomba yang ditugaskan kepada Anda.
                @endif
            </p>
        </div>
        @if ($firstCompetition)
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('start-list.pdf', [$firstCompetition, 'inline' => 1]) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">PDF acara</a>
                <a href="{{ route('results.pdf', [$firstCompetition, 'inline' => 1]) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">PDF buku hasil</a>
            </div>
        @endif
    </div>

    @if ($empty)
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white px-5 py-6 text-sm leading-6 text-slate-700">
            @if ($manages)
                Belum ada seri untuk diinput. Bagi seri dulu di
                @if ($seedingUrl = \App\Support\AdminNavigation::url('admin.seeding.index'))
                    <a href="{{ $seedingUrl }}" class="font-medium text-teal-800 hover:underline">Pembagian seri</a>.
                @else
                    <span class="font-medium">Pembagian seri</span> (belum ada acara).
                @endif
            @else
                Belum ada nomor lomba yang ditugaskan kepada Anda. Hubungi panitia untuk penugasan.
            @endif
        </div>
    @else
        @if ($nextHeat)
            <div class="mt-5 flex flex-col gap-3 rounded-2xl border border-teal-200 bg-teal-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm leading-6 text-teal-950">
                    <p class="font-semibold">Langkah berikutnya: input hasil</p>
                    <p>
                        {{ $nextHeat['heat']->event->paddedEventNumber() }}
                        {{ $nextHeat['heat']->event->formattedName() }}
                        · {{ $nextHeat['heat']->ageGroup?->name }}
                        · Seri {{ $nextHeat['heat']->heat_number }}
                    </p>
                </div>
                <a href="{{ route('judge.heats.show', $nextHeat['heat']) }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">
                    Buka seri ini
                </a>
            </div>
        @elseif ($heatTotal > 0)
            <div class="mt-5 rounded-2xl border border-teal-200 bg-teal-50 px-5 py-4 text-sm leading-6 text-teal-950">
                <p class="font-semibold">Semua seri sudah dikunci</p>
                <p>Tidak ada input tersisa di tugas Anda.</p>
            </div>
        @endif

        <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Progres input</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $lockedCount }} / {{ $heatTotal }} seri dikunci</p>
                </div>
                <p class="text-sm font-semibold text-slate-900">{{ $progress }}%</p>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}">
                <div class="h-full rounded-full bg-teal-600" style="width: {{ $progress }}%"></div>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <a href="{{ route('judge.tasks', ['status' => 'pending']) }}" class="rounded-xl border px-4 py-3 {{ $statusFilter === 'pending' ? 'border-amber-300 bg-amber-50' : 'border-slate-200 hover:bg-slate-50' }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Perlu input</p>
                    <p class="mt-1 text-xl font-semibold {{ $pendingCount > 0 ? 'text-amber-700' : 'text-teal-800' }}">{{ $pendingCount }}</p>
                </a>
                <a href="{{ route('judge.tasks', ['status' => 'ready']) }}" class="rounded-xl border px-4 py-3 {{ $statusFilter === 'ready' ? 'border-sky-300 bg-sky-50' : 'border-slate-200 hover:bg-slate-50' }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Siap dikunci</p>
                    <p class="mt-1 text-xl font-semibold">{{ $readyCount }}</p>
                </a>
                <a href="{{ route('judge.tasks', ['status' => 'locked']) }}" class="rounded-xl border px-4 py-3 {{ $statusFilter === 'locked' ? 'border-teal-300 bg-teal-50' : 'border-slate-200 hover:bg-slate-50' }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Terkunci</p>
                    <p class="mt-1 text-xl font-semibold text-teal-800">{{ $lockedCount }}</p>
                </a>
            </div>
            @if ($statusFilter !== '')
                <p class="mt-3 text-sm">
                    <a href="{{ route('judge.tasks') }}" class="font-medium text-teal-800 hover:underline">Tampilkan semua seri</a>
                </p>
            @endif
        </section>

        <details class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 text-sm leading-6 text-slate-700">
            <summary class="cursor-pointer font-semibold text-slate-900">Cara kerja halaman ini</summary>
            <ol class="mt-3 list-decimal space-y-2 border-t border-slate-100 pt-3 pl-5">
                <li>Buka seri yang masih <strong>Belum dikunci</strong>.</li>
                <li>Isi waktu atau status tiap lintasan. Tersimpan otomatis.</li>
                <li>Jika semua lintasan terisi sudah tercatat, tekan <strong>Kunci seri</strong>.</li>
                <li>Status: <strong>OK</strong> waktu sah, <strong>DNS</strong> tidak start, <strong>DNF</strong> tidak finis, <strong>DSQ</strong> diskualifikasi (wajib kode alasan).</li>
            </ol>
        </details>

        <div class="mt-5 space-y-4">
            @php
                $groupedTasks = $tasks->groupBy(fn ($task) => $task['event']->competition_id);
                $multipleCompetitions = $groupedTasks->count() > 1;
            @endphp
            @forelse ($groupedTasks as $competitionTasks)
                @php
                    $competitionName = $competitionTasks->first()['event']->competition?->name;
                @endphp
                <div class="space-y-4">
                    @if ($multipleCompetitions)
                        <h2 class="text-sm font-semibold text-slate-700">{{ $competitionName }}</h2>
                    @endif

                    @foreach ($competitionTasks as $task)
                        @php $event = $task['event']; @endphp
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                                <div>
                                    <h2 class="font-medium text-slate-900">
                                        <span class="font-mono text-teal-800">{{ $event->paddedEventNumber() }}</span>
                                        {{ $event->formattedName() }}
                                    </h2>
                                    <p class="text-xs text-slate-500">{{ $event->competition?->name }} · Sesi {{ $event->session }}</p>
                                </div>
                                <p class="text-sm {{ $task['complete'] ? 'text-teal-700' : 'text-slate-600' }}">
                                    {{ $task['locked'] }} / {{ $task['total'] }} seri dikunci
                                </p>
                            </div>
                            <ul class="divide-y divide-slate-100">
                                @foreach ($task['heats'] as $row)
                                    @php $heat = $row['heat']; @endphp
                                    <li>
                                        <a href="{{ route('judge.heats.show', $heat) }}" class="flex min-h-14 items-center justify-between gap-3 px-4 py-3 text-sm hover:bg-slate-50">
                                            <span>
                                                <span class="font-medium text-slate-900">{{ $heat->ageGroup?->name }} · Seri {{ $heat->heat_number }}</span>
                                                <span class="mt-0.5 block text-xs text-slate-500">{{ $row['recorded'] }}/{{ $row['occupied'] }} lintasan tercatat</span>
                                            </span>
                                            @if ($row['locked'])
                                                <span class="rounded-full bg-teal-100 px-2.5 py-1 text-xs font-medium text-teal-800">Terkunci</span>
                                            @elseif ($row['ready'])
                                                <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-900">Siap dikunci</span>
                                            @else
                                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-900">Belum dikunci</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </div>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-8 text-center text-sm text-slate-600">
                    Tidak ada seri yang cocok dengan saringan.
                </div>
            @endforelse
        </div>

        @include('partials.pagination', ['paginator' => $tasks])
        <p class="mt-3 text-xs text-slate-500">Daftar disegarkan otomatis setiap menit jika tab ini terbuka.</p>
    @endif
@endsection

@push('scripts')
    <script>
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                window.location.reload();
            }
        }, 60000);
    </script>
@endpush
