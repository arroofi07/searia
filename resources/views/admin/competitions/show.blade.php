@php
    use App\Enums\CompetitionStatus;
@endphp

@extends('layouts.app')

@section('title', $competition->name)

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ $competition->name }}</h1>
            <p class="text-sm text-slate-500">{{ $competition->venue }}, {{ $competition->city }} · {{ $competition->status->label() }}</p>
            <p class="mt-2 text-sm">
                <a href="{{ route('admin.activity-logs.subject') }}?{{ http_build_query(['type' => \App\Models\Competition::class, 'id' => $competition->id]) }}" class="text-teal-800 hover:underline">Riwayat audit kejuaraan</a>
            </p>
        </div>
        <form method="POST" action="{{ route('admin.competitions.duplicate', $competition) }}">
            @csrf
            <button class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Gandakan</button>
        </form>
    </div>

    @if (session('pending_seeding'))
        @include('admin.seeding._pending-list', [
            'items' => session('pending_seeding'),
            'title' => 'Belum bisa ke Sudah diseeding',
            'intro' => 'Nomor berikut sudah punya peserta disetujui, tetapi belum punya seri dan lintasan.',
        ])
    @elseif ($errors->has('status'))
        <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-800">
            {{ $errors->first('status') }}
        </div>
    @endif

    <dl class="mt-6 grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-3">
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Tanggal</dt>
            <dd class="mt-1">{{ $competition->start_date->translatedFormat('d M Y') }} – {{ $competition->end_date->translatedFormat('d M Y') }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Lintasan</dt>
            <dd class="mt-1">{{ $competition->pool_lanes }} × {{ $competition->pool_length }} m</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Kelompok / nomor</dt>
            <dd class="mt-1">{{ $competition->age_groups_count }} grup · {{ $competition->events_count }} nomor</dd>
        </div>
    </dl>

    <div class="mt-6 rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="font-medium">Pengaturan acara</h2>
        <p class="mt-1 text-sm text-slate-500">Siapkan data sebelum membuka pendaftaran.</p>
        <div class="mt-4 grid gap-2 sm:grid-cols-2">
            <a href="{{ route('admin.competitions.edit', $competition) }}" class="rounded-md border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50">Data acara</a>
            <a href="{{ route('admin.competitions.age-groups.index', $competition) }}" class="rounded-md border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50">Kelompok umur</a>
            <a href="{{ route('admin.competitions.events.index', $competition) }}" class="rounded-md border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50">Nomor lomba</a>
            <a href="{{ route('admin.competitions.eligibility', $competition) }}" class="rounded-md border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50">Matriks kelayakan</a>
            <a href="{{ route('admin.competitions.readiness', $competition) }}" class="rounded-md border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50">Kesiapan</a>
            <a href="{{ route('admin.judges.edit', $competition) }}" class="rounded-md border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50">Penugasan juri</a>
        </div>
    </div>

    @php
        $next = $competition->status->allowedForward()[0] ?? null;
        $back = $competition->status->allowedBackward()[0] ?? null;
        $canRevert = $back !== null && auth()->user()?->can('revert', $competition);
        $revertLabel = $competition->status === CompetitionStatus::Seeded && $back
            ? 'Kembalikan ke '.$back->label()
            : 'Mundurkan status';
    @endphp

    <div class="mt-6 max-w-xl rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="font-medium">Ubah status</h2>
        <p class="mt-1 text-sm text-slate-500">
            Hanya perpindahan berurutan yang diizinkan.
            Dari Sudah diseeding, panitia dapat mengembalikan ke Pendaftaran ditutup untuk mengulang pembagian seri.
            Mundur dari status lain hanya Super Admin.
        </p>

        @if ($next)
            @if ($next === CompetitionStatus::Registration && ! $ready)
                <p class="mt-4 text-sm text-amber-700">Pendaftaran belum dapat dibuka. Lihat halaman kesiapan.</p>
            @else
                <button type="button" class="mt-4 rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800" data-open-modal="status-forward-modal">
                    Lanjut ke {{ $next->label() }}
                </button>
            @endif
        @endif

        @if ($canRevert)
            <button type="button" class="mt-4 rounded-md bg-amber-700 px-4 py-2 text-sm font-medium text-white hover:bg-amber-800" data-open-modal="status-back-modal">
                {{ $revertLabel }}
            </button>
            @error('reason') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        @endif
    </div>

    @if ($next && ! ($next === CompetitionStatus::Registration && ! $ready))
        <dialog id="status-forward-modal" class="admin-modal w-[min(100%-2rem,28rem)] rounded-2xl border-0 bg-white p-0 text-slate-900 shadow-2xl" aria-labelledby="status-forward-title">
            <form method="POST" action="{{ route('admin.competitions.status', $competition) }}" class="p-5">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $next->value }}">
                <h2 id="status-forward-title" class="text-lg font-semibold">Ubah status</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Status akan maju ke langkah berikutnya. Buku acara atau hasil publik mengikuti status ini.
                </p>
                <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-800">
                    {{ $competition->status->label() }}
                    <span class="mx-1 text-slate-400">→</span>
                    <strong>{{ $next->label() }}</strong>
                </p>
                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50" data-close-modal>
                        Batal
                    </button>
                    <button class="inline-flex min-h-11 items-center justify-center rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">
                        Lanjut ke {{ $next->label() }}
                    </button>
                </div>
            </form>
        </dialog>
    @endif

    @if ($canRevert)
        <dialog id="status-back-modal" class="admin-modal w-[min(100%-2rem,28rem)] rounded-2xl border-0 bg-white p-0 text-slate-900 shadow-2xl" aria-labelledby="status-back-title">
            <form method="POST" action="{{ route('admin.competitions.status', $competition) }}" class="p-5">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $back->value }}">
                <h2 id="status-back-title" class="text-lg font-semibold">{{ $revertLabel }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    @if ($competition->status === CompetitionStatus::Seeded)
                        Buku acara publik disembunyikan sampai status maju lagi. Seri yang sudah ada tetap tersimpan; ulangi pembagian jika ada peserta baru.
                    @else
                        Hanya perpindahan berurutan yang diizinkan. Mundur dari status ini hanya Super Admin.
                    @endif
                </p>
                <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-950">
                    {{ $competition->status->label() }}
                    <span class="mx-1 text-amber-400">→</span>
                    <strong>{{ $back->label() }}</strong>
                </p>
                <label for="reason" class="mt-4 block text-sm font-medium text-slate-700">Alasan mundur ke {{ $back->label() }}</label>
                <textarea id="reason" name="reason" rows="2" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('reason') }}</textarea>
                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50" data-close-modal>
                        Batal
                    </button>
                    <button class="inline-flex min-h-11 items-center justify-center rounded-md bg-amber-700 px-4 py-2 text-sm font-medium text-white hover:bg-amber-800">
                        {{ $revertLabel }}
                    </button>
                </div>
            </form>
        </dialog>
    @endif
@endsection

@push('scripts')
    <style>
        dialog.admin-modal { margin: auto; }
        dialog.admin-modal::backdrop { background: rgb(15 23 42 / 0.55); }
    </style>
    <script>
        (() => {
            document.querySelectorAll('[data-open-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    document.getElementById(button.dataset.openModal)?.showModal();
                });
            });

            document.querySelectorAll('dialog.admin-modal').forEach((dialog) => {
                dialog.querySelectorAll('[data-close-modal]').forEach((button) => {
                    button.addEventListener('click', () => dialog.close());
                });
                dialog.addEventListener('click', (event) => {
                    if (event.target === dialog) {
                        dialog.close();
                    }
                });
            });

            @if ($errors->has('reason'))
                document.getElementById('status-back-modal')?.showModal();
            @endif
        })();
    </script>
@endpush
