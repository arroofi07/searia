@php
    use App\Enums\ClubStatus;
@endphp

@extends('layouts.app')

@section('title', $club->name)

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ $club->name }}</h1>
            <p class="text-sm text-slate-500">{{ $club->type->label() }} · {{ $club->city }}{{ $club->province ? ', '.$club->province : '' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.clubs.edit', $club) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Ubah data</a>
            @if ($club->status !== ClubStatus::Verified)
                <form method="POST" action="{{ route('admin.clubs.verify', $club) }}">
                    @csrf
                    @method('PATCH')
                    <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Setujui</button>
                </form>
            @endif
        </div>
    </div>

    <dl class="mt-6 grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Status</dt>
            <dd class="mt-1 font-medium">{{ $club->status->label() }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Aktif</dt>
            <dd class="mt-1 font-medium">{{ $club->is_active ? 'Ya' : 'Tidak' }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Kontak</dt>
            <dd class="mt-1">{{ $club->contact_name ?: '—' }} · {{ $club->contact_phone ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Jumlah atlet</dt>
            <dd class="mt-1">{{ $club->athletes_count }}</dd>
        </div>
        @if ($club->rejection_reason)
            <div class="sm:col-span-2">
                <dt class="text-xs uppercase tracking-wide text-slate-500">Alasan penolakan</dt>
                <dd class="mt-1 text-red-700">{{ $club->rejection_reason }}</dd>
            </div>
        @endif
    </dl>

    @if ($club->status !== ClubStatus::Rejected)
        <form method="POST" action="{{ route('admin.clubs.reject', $club) }}" class="mt-6 max-w-xl space-y-3 rounded-lg border border-slate-200 bg-white p-5">
            @csrf
            @method('PATCH')
            <h2 class="font-medium">Tolak klub</h2>
            <textarea name="rejection_reason" rows="3" required placeholder="Alasan penolakan wajib diisi"
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('rejection_reason') }}</textarea>
            @error('rejection_reason') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <button class="rounded-md bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800">Tolak</button>
        </form>
    @endif

    <form method="POST" action="{{ route('admin.clubs.destroy', $club) }}" class="mt-6" onsubmit="return confirm('Hapus atau nonaktifkan klub ini?')">
        @csrf
        @method('DELETE')
        <button class="text-sm text-red-700 hover:underline">Hapus klub</button>
    </form>
@endsection
