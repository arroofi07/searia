@php
    use App\Enums\CompetitionStatus;
@endphp

@extends('layouts.app')

@section('title', 'Kejuaraan')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Kejuaraan</h1>
            <p class="text-sm text-slate-500">Susun identitas acara, kelompok umur, nomor lomba, dan matriks kelayakan.</p>
        </div>
        @can('create', App\Models\Competition::class)
            <a href="{{ route('admin.competitions.create') }}" class="rounded-md bg-teal-700 px-4 py-2 text-center text-sm font-medium text-white hover:bg-teal-800">Tambah kejuaraan</a>
        @endcan
    </div>

    <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Nama</th>
                    <th class="px-4 py-3 font-medium">Tanggal</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Grup</th>
                    <th class="px-4 py-3 font-medium">Nomor</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($competitions as $competition)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.competitions.show', $competition) }}" class="font-medium text-teal-800 hover:underline">{{ $competition->name }}</a>
                            <div class="text-xs text-slate-500">{{ $competition->city }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $competition->start_date->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $competition->status->label() }}</td>
                        <td class="px-4 py-3">{{ $competition->age_groups_count }}</td>
                        <td class="px-4 py-3">{{ $competition->events_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada kejuaraan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $competitions->links() }}</div>
@endsection
