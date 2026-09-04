@extends('layouts.app')

@section('title', $athlete->full_name)

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ $athlete->full_name }}</h1>
            <p class="text-sm text-slate-500">{{ $athlete->club->name }} · {{ $athlete->gender->label() }} · {{ $athlete->birth_year }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('update', $athlete)
                <a href="{{ route('athletes.edit', $athlete) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Ubah</a>
            @endcan
            @can('merge', $athlete)
                <a href="{{ route('admin.athletes.merge.create', $athlete) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Gabungkan</a>
            @endcan
        </div>
    </div>

    @if ($similarAthletes->isNotEmpty())
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            <p class="font-medium">Ditemukan atlet mirip di klub yang sama dengan tahun lahir yang sama.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($similarAthletes as $similar)
                    <li>
                        <a href="{{ route('athletes.show', $similar) }}" class="underline">{{ $similar->full_name }}</a>
                        ({{ $similar->birth_year }})
                    </li>
                @endforeach
            </ul>
            @can('merge', $athlete)
                <a href="{{ route('admin.athletes.merge.create', $athlete) }}" class="mt-2 inline-block font-medium underline">Tinjau penggabungan</a>
            @endcan
        </div>
    @endif

    <dl class="mt-6 grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Status</dt>
            <dd class="mt-1">{{ $athlete->is_active ? 'Aktif' : 'Nonaktif' }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Identitas</dt>
            <dd class="mt-1">{{ $athlete->identity_number ?: '—' }}</dd>
        </div>
        @if ($athlete->photo_path)
            <div class="sm:col-span-2">
                <dt class="text-xs uppercase tracking-wide text-slate-500">Foto</dt>
                <dd class="mt-2"><img src="{{ Storage::url($athlete->photo_path) }}" alt="{{ $athlete->full_name }}" class="h-32 rounded object-cover"></dd>
            </div>
        @endif
    </dl>

    @can('delete', $athlete)
        <form method="POST" action="{{ route('athletes.destroy', $athlete) }}" class="mt-6" onsubmit="return confirm('Hapus atau arsipkan atlet ini?')">
            @csrf
            @method('DELETE')
            <button class="text-sm text-red-700 hover:underline">Hapus atau arsipkan</button>
        </form>
    @endcan
@endsection
