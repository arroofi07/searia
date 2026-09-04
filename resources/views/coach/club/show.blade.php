@extends('layouts.app')

@section('title', 'Profil klub')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ $club->name }}</h1>
            <p class="text-sm text-slate-500">{{ $club->type->label() }} · {{ $club->city }}</p>
        </div>
        @can('update', $club)
            <a href="{{ route('coach.club.edit', $club) }}" class="rounded-md bg-teal-700 px-4 py-2 text-center text-sm font-medium text-white hover:bg-teal-800">Ubah profil</a>
        @endcan
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[12rem_1fr]">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            @if ($club->logo_path)
                <img src="{{ Storage::url($club->logo_path) }}" alt="Logo {{ $club->name }}" class="mx-auto h-32 w-32 object-contain">
            @else
                <div class="flex h-32 items-center justify-center text-sm text-slate-400">Belum ada logo</div>
            @endif
        </div>
        <dl class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Status</dt>
                <dd class="mt-1 font-medium">{{ $club->status->label() }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Singkatan</dt>
                <dd class="mt-1">{{ $club->short_name ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Kontak</dt>
                <dd class="mt-1">{{ $club->contact_name ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Telepon</dt>
                <dd class="mt-1">{{ $club->contact_phone ?: '—' }}</dd>
            </div>
            @if ($club->rejection_reason)
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Alasan panitia</dt>
                    <dd class="mt-1 text-red-700">{{ $club->rejection_reason }}</dd>
                </div>
            @endif
        </dl>
    </div>
@endsection
