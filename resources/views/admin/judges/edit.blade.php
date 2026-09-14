@php
    $progress = $eventTotal > 0 ? (int) round(($assignedCount / $eventTotal) * 100) : 0;
    $statusFilter = (string) ($filters['status'] ?? '');
    $savedEventId = (int) session('saved_event_id', 0);
@endphp

@extends('layouts.app')

@section('title', 'Penugasan juri')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Penugasan juri</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $competition->name }}</p>
            <p class="mt-1 text-sm text-slate-500">Tentukan siapa yang mencatat hasil di tiap nomor lomba.</p>
        </div>
        <a href="{{ route('admin.results.index', $competition) }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">
            Ke hasil lomba
        </a>
    </div>

    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'judges'])

    @error('judge_ids')
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    @if ($eventTotal === 0)
        <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-5 py-4 text-sm text-slate-700">
            Belum ada nomor lomba.
            <a href="{{ route('admin.competitions.events.index', $competition) }}" class="font-medium text-teal-800 hover:underline">Lengkapi nomor lomba</a>
            dulu, lalu kembali ke halaman ini.
        </div>
    @elseif ($judges->isEmpty())
        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-950">
            <p class="font-semibold">Belum ada akun juri yang aktif</p>
            <p>Buat akun berperan Juri (atau pakai Panitia) sebelum menugaskan nomor.</p>
        </div>
    @elseif ($unassignedCount > 0)
        <div class="mt-4 rounded-2xl border border-teal-200 bg-teal-50 px-5 py-4 text-sm leading-6 text-teal-950">
            <p class="font-semibold">Langkah berikutnya: isi nomor yang masih kosong</p>
            <p>{{ $unassignedCount }} nomor belum punya juri. Pilih juri di kotak bawah, lalu terapkan ke nomor kosong — tidak menimpa yang sudah diisi.</p>
        </div>
    @else
        <div class="mt-4 rounded-2xl border border-teal-200 bg-teal-50 px-5 py-4 text-sm leading-6 text-teal-950">
            <p class="font-semibold">Semua nomor sudah ada juri</p>
            <p>Ubah per nomor jika perlu, atau ganti sekaligus lewat saringan di bawah.</p>
        </div>
    @endif

    <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Progres penugasan</p>
                <p class="mt-1 text-sm text-slate-700">{{ $assignedCount }} dari {{ $eventTotal }} nomor sudah ada juri</p>
            </div>
            <p class="text-sm font-semibold text-slate-900">{{ $progress }}%</p>
        </div>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}">
            <div class="h-full rounded-full bg-teal-600" style="width: {{ $progress }}%"></div>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            <a href="{{ route('admin.judges.edit', $competition) }}" class="rounded-xl border px-4 py-3 {{ $statusFilter === '' ? 'border-teal-300 bg-teal-50' : 'border-slate-200 hover:bg-slate-50' }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Semua nomor</p>
                <p class="mt-1 text-xl font-semibold">{{ $eventTotal }}</p>
            </a>
            <a href="{{ route('admin.judges.edit', [$competition, 'status' => 'assigned', 'q' => $filters['q'], 'session' => $filters['session']]) }}" class="rounded-xl border px-4 py-3 {{ $statusFilter === 'assigned' ? 'border-teal-300 bg-teal-50' : 'border-slate-200 hover:bg-slate-50' }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sudah ada juri</p>
                <p class="mt-1 text-xl font-semibold text-teal-800">{{ $assignedCount }}</p>
            </a>
            <a href="{{ route('admin.judges.edit', [$competition, 'status' => 'unassigned', 'q' => $filters['q'], 'session' => $filters['session']]) }}" class="rounded-xl border px-4 py-3 {{ $statusFilter === 'unassigned' ? 'border-amber-300 bg-amber-50' : 'border-slate-200 hover:bg-slate-50' }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Belum ditugaskan</p>
                <p class="mt-1 text-xl font-semibold {{ $unassignedCount > 0 ? 'text-amber-700' : 'text-teal-800' }}">{{ $unassignedCount }}</p>
            </a>
        </div>
    </section>

    @php
        $roster = $judges->filter(fn ($judge): bool => $judge->isJuri() || $judge->assigned_events_count > 0);
    @endphp
    @if ($roster->isNotEmpty())
        <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Beban juri</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($roster as $judge)
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm">
                        <span class="font-medium text-slate-900">{{ $judge->name }}</span>
                        <span class="text-xs text-slate-500">{{ $judge->assigned_events_count }} nomor · {{ $judge->role->label() }}</span>
                    </span>
                @endforeach
            </div>
        </section>
    @endif

    <details class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 text-sm leading-6 text-slate-700">
        <summary class="cursor-pointer font-semibold text-slate-900">Cara menugaskan juri</summary>
        <div class="mt-3 border-t border-slate-100 pt-3">
            <ol class="list-decimal space-y-2 pl-5">
                <li>Pilih juri di <strong>Isi cepat</strong>, lalu tekan <strong>Isi nomor yang masih kosong</strong>.</li>
                <li>Cek daftar nomor. Yang masih kosong ditandai kuning.</li>
                <li>Buka <strong>Ubah juri</strong> hanya untuk nomor yang perlu dikecualikan.</li>
                <li>Satu nomor boleh lebih dari satu juri. Juri hanya melihat nomor yang ditugaskan kepadanya.</li>
            </ol>
        </div>
    </details>

    @if ($eventTotal > 0 && $judges->isNotEmpty())
        <form method="POST" action="{{ route('admin.judges.update', $competition) }}" class="mt-5 rounded-2xl border border-slate-200 bg-white p-5" data-bulk-assign>
            @csrf
            @method('PUT')
            <input type="hidden" name="q" value="{{ $filters['q'] }}">
            <input type="hidden" name="session" value="{{ $filters['session'] }}">
            <input type="hidden" name="status" value="{{ $filters['status'] }}">

            <h2 class="font-semibold text-slate-900">Isi cepat</h2>
            <p class="mt-1 text-sm text-slate-600">Pilih juri, lalu terapkan ke banyak nomor sekaligus. Saringan di bawah ikut dipakai.</p>

            <div class="mt-4">
                @include('admin.judges._picker', ['judges' => $judges, 'selected' => collect(), 'showCounts' => true])
            </div>

            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                <button
                    name="intent"
                    value="fill_empty"
                    class="inline-flex min-h-11 items-center justify-center rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800 disabled:cursor-not-allowed disabled:bg-slate-300"
                    @disabled($unassignedFilteredCount === 0)
                >
                    Isi {{ $unassignedFilteredCount }} nomor yang masih kosong
                </button>
                @if ($filteredCount > 0)
                    <button
                        name="intent"
                        value="replace_filtered"
                        class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50"
                        data-confirm="Ganti juri di {{ $filteredCount }} nomor yang cocok dengan saringan? Penugasan lama di nomor itu akan diganti."
                    >
                        Ganti juri di {{ $filteredCount }} nomor yang tampil
                    </button>
                @endif
            </div>
            <p class="mt-2 text-xs text-slate-500">
                “Isi yang masih kosong” tidak menimpa nomor yang sudah ada juri.
                “Ganti” menimpa semua nomor yang cocok dengan saringan, termasuk yang di halaman lain.
            </p>
        </form>
    @endif

    <form method="GET" action="{{ route('admin.judges.edit', $competition) }}" class="mt-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="sm:col-span-2 lg:col-span-2">
            <label for="judge-q" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Cari nomor</label>
            <input id="judge-q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Nomor, gaya, atau putra/putri" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="judge-session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Sesi</label>
            <select id="judge-session" name="session" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua sesi</option>
                @foreach ($sessions as $session)
                    <option value="{{ $session }}" @selected((string) $filters['session'] === (string) $session)>Sesi {{ $session }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="judge-status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
            <select id="judge-status" name="status" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua</option>
                <option value="unassigned" @selected($statusFilter === 'unassigned')>Belum ditugaskan</option>
                <option value="assigned" @selected($statusFilter === 'assigned')>Sudah ada juri</option>
            </select>
        </div>
        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
            <button class="inline-flex min-h-11 items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Saring</button>
            <a href="{{ route('admin.judges.edit', $competition) }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <div class="mt-5 space-y-4">
        @forelse ($events->groupBy('session') as $session => $sessionEvents)
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <h2 class="border-b border-slate-100 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800">Sesi {{ $session }}</h2>
                <div class="divide-y divide-slate-100">
                    @foreach ($sessionEvents as $event)
                        @php
                            $assigned = $event->judges;
                            $empty = $assigned->isEmpty();
                            $open = $savedEventId === $event->id;
                        @endphp
                        <article class="px-4 py-4 {{ $empty ? 'bg-amber-50/60' : '' }}">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="font-medium text-slate-900">
                                        <span class="font-mono text-teal-800">{{ $event->paddedEventNumber() }}</span>
                                        {{ $event->formattedName() }}
                                    </p>
                                    @if ($empty)
                                        <p class="mt-2 text-sm font-medium text-amber-800">Belum ada juri</p>
                                    @else
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach ($assigned as $judge)
                                                <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-900">{{ $judge->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <details class="mt-3" @if ($open) open @endif>
                                <summary class="cursor-pointer text-sm font-medium text-teal-800 hover:underline">Ubah juri nomor ini</summary>
                                <form method="POST" action="{{ route('admin.judges.update', $competition) }}" class="mt-3 space-y-3">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="event_id" value="{{ $event->id }}">
                                    <input type="hidden" name="q" value="{{ $filters['q'] }}">
                                    <input type="hidden" name="session" value="{{ $filters['session'] }}">
                                    <input type="hidden" name="status" value="{{ $filters['status'] }}">

                                    @include('admin.judges._picker', [
                                        'judges' => $judges,
                                        'selected' => $assigned->pluck('id'),
                                        'showCounts' => false,
                                    ])

                                    <div class="flex flex-col gap-2 sm:flex-row">
                                        <button name="intent" value="event" class="inline-flex min-h-11 items-center justify-center rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">
                                            Simpan nomor ini
                                        </button>
                                        <button name="intent" value="fill_session" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">
                                            Isi sisa sesi {{ $event->session }} yang kosong
                                        </button>
                                    </div>
                                </form>
                            </details>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-8 text-center text-sm text-slate-600">
                Tidak ada nomor yang cocok dengan saringan.
            </div>
        @endforelse
    </div>

    @include('partials.pagination', ['paginator' => $events])
@endsection

@push('scripts')
    <script>
        (() => {
            document.querySelectorAll('[data-judge-picker-root]').forEach((root) => {
                const picker = root.querySelector('[data-judge-picker]');
                const toggle = root.querySelector('[data-show-officials]');

                picker?.addEventListener('change', (event) => {
                    const input = event.target;
                    if (!(input instanceof HTMLInputElement) || input.type !== 'checkbox') {
                        return;
                    }
                    const label = input.closest('label');
                    if (!label) {
                        return;
                    }
                    label.classList.toggle('border-teal-300', input.checked);
                    label.classList.toggle('bg-teal-50', input.checked);
                    label.classList.toggle('border-slate-200', !input.checked);
                });

                toggle?.addEventListener('change', () => {
                    root.querySelectorAll('[data-official="1"]').forEach((label) => {
                        const input = label.querySelector('input[type="checkbox"]');
                        const keep = input instanceof HTMLInputElement && input.checked;
                        label.classList.toggle('hidden', !toggle.checked && !keep);
                    });
                });
            });

            const bulk = document.querySelector('[data-bulk-assign]');
            if (bulk instanceof HTMLFormElement) {
                bulk.addEventListener('submit', (event) => {
                    const submitter = event.submitter;
                    if (!(submitter instanceof HTMLButtonElement)) {
                        return;
                    }
                    const message = submitter.getAttribute('data-confirm');
                    if (message && !window.confirm(message)) {
                        event.preventDefault();
                    }
                });
            }
        })();
    </script>
@endpush
