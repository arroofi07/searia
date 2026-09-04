@php
    use App\Enums\ClubStatus;
@endphp

@extends('layouts.app')

@section('title', 'Daftar klub')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Klub dan sekolah</h1>
            <p class="text-sm text-slate-500">Verifikasi pendaftaran klub dan perbaiki data yang salah.</p>
        </div>
        @can('create', App\Models\Club::class)
            <a href="{{ route('admin.clubs.create') }}" class="rounded-md bg-teal-700 px-4 py-2 text-center text-sm font-medium text-white hover:bg-teal-800">Tambah klub</a>
        @endcan
    </div>

    <form method="GET" class="mt-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-4">
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama klub"
            class="rounded-md border border-slate-300 px-3 py-2 text-sm sm:col-span-2">
        <select name="status" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua status</option>
            @foreach (ClubStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <select name="city" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua kota</option>
            @foreach ($cities as $city)
                <option value="{{ $city }}" @selected(($filters['city'] ?? '') === $city)>{{ $city }}</option>
            @endforeach
        </select>
        <div class="flex gap-2 sm:col-span-4">
            <button class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white">Saring</button>
            <a href="{{ route('admin.clubs.index') }}" class="rounded-md px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Reset</a>
        </div>
    </form>

    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Nama</th>
                    <th class="px-4 py-3 font-medium">Jenis</th>
                    <th class="px-4 py-3 font-medium">Kota</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Atlet</th>
                    <th class="px-4 py-3 font-medium">Aktif</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clubs as $club)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.clubs.show', $club) }}" class="font-medium text-teal-800 hover:underline">{{ $club->name }}</a>
                            @if ($club->short_name)
                                <div class="text-xs text-slate-500">{{ $club->short_name }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $club->type->label() }}</td>
                        <td class="px-4 py-3">{{ $club->city }}</td>
                        <td class="px-4 py-3">{{ $club->status->label() }}</td>
                        <td class="px-4 py-3">{{ $club->athletes_count }}</td>
                        <td class="px-4 py-3">{{ $club->is_active ? 'Ya' : 'Tidak' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada klub.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $clubs->links() }}</div>
@endsection
