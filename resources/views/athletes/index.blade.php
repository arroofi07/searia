@php
    use App\Enums\Gender;
@endphp

@extends('layouts.app')

@section('title', 'Daftar atlet')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Atlet</h1>
            <p class="text-sm text-slate-500">Tambah, ubah, atau arsipkan atlet. Data dipakai ulang lintas kejuaraan.</p>
        </div>
        @can('create', App\Models\Athlete::class)
            <a href="{{ route('athletes.create') }}" class="rounded-md bg-teal-700 px-4 py-2 text-center text-sm font-medium text-white hover:bg-teal-800">Tambah atlet</a>
        @endcan
    </div>

    <form method="GET" class="mt-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-4">
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama"
            class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <input type="number" name="birth_year" value="{{ $filters['birth_year'] ?? '' }}" placeholder="Tahun lahir"
            class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <select name="gender" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua jenis kelamin</option>
            @foreach (Gender::cases() as $gender)
                <option value="{{ $gender->value }}" @selected(($filters['gender'] ?? '') === $gender->value)>{{ $gender->label() }}</option>
            @endforeach
        </select>
        @if ($clubs->isNotEmpty())
            <select name="club_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua klub</option>
                @foreach ($clubs as $club)
                    <option value="{{ $club->id }}" @selected((string) ($filters['club_id'] ?? '') === (string) $club->id)>{{ $club->name }}</option>
                @endforeach
            </select>
        @endif
        <div class="flex gap-2 sm:col-span-4">
            <button class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white">Saring</button>
            <a href="{{ route('athletes.index') }}" class="rounded-md px-4 py-2 text-sm text-slate-600">Reset</a>
        </div>
    </form>

    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Nama</th>
                    <th class="px-4 py-3 font-medium">Jenis kelamin</th>
                    <th class="px-4 py-3 font-medium">Tahun lahir</th>
                    <th class="px-4 py-3 font-medium">Klub</th>
                    <th class="px-4 py-3 font-medium">Aktif</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($athletes as $athlete)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <a href="{{ route('athletes.show', $athlete) }}" class="font-medium text-teal-800 hover:underline">{{ $athlete->full_name }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $athlete->gender->label() }}</td>
                        <td class="px-4 py-3">{{ $athlete->birth_year }}</td>
                        <td class="px-4 py-3">{{ $athlete->club->name }}</td>
                        <td class="px-4 py-3">{{ $athlete->is_active ? 'Ya' : 'Tidak' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada atlet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $athletes->links() }}</div>
@endsection
