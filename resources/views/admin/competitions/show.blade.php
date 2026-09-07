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

    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'show'])

    @error('status')
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

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

    @php
        $next = $competition->status->allowedForward()[0] ?? null;
        $back = $competition->status->allowedBackward()[0] ?? null;
    @endphp

    <div class="mt-6 max-w-xl rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="font-medium">Ubah status</h2>
        <p class="mt-1 text-sm text-slate-500">Hanya perpindahan berurutan yang diizinkan. Mundur hanya untuk Super Admin.</p>

        @if ($next)
            @if ($next === CompetitionStatus::Registration && ! $ready)
                <p class="mt-4 text-sm text-amber-700">Pendaftaran belum dapat dibuka. Lihat halaman kesiapan.</p>
            @else
                <form method="POST" action="{{ route('admin.competitions.status', $competition) }}" class="mt-4">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $next->value }}">
                    <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">
                        Lanjut ke {{ $next->label() }}
                    </button>
                </form>
            @endif
        @endif

        @if ($back && auth()->user()?->isSuperAdmin())
            <form method="POST" action="{{ route('admin.competitions.status', $competition) }}" class="mt-4 space-y-2">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $back->value }}">
                <label for="reason" class="block text-sm font-medium text-slate-700">Alasan mundur ke {{ $back->label() }}</label>
                <textarea id="reason" name="reason" rows="2" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></textarea>
                @error('reason') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <button class="rounded-md bg-amber-700 px-4 py-2 text-sm font-medium text-white hover:bg-amber-800">Mundurkan status</button>
            </form>
        @endif
    </div>
@endsection
