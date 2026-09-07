@extends('layouts.app')

@section('title', 'Pendaftaran')

@section('content')
    <h1 class="text-2xl font-semibold">Pendaftaran kejuaraan</h1>
    <p class="mt-1 text-sm text-slate-500">Pilih kejuaraan yang sedang membuka pendaftaran.</p>

    <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Kejuaraan</th>
                    <th class="px-4 py-3 font-medium">Tanggal</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($competitions as $competition)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-medium">{{ $competition->name }}</td>
                        <td class="px-4 py-3">{{ $competition->start_date->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('registrations.create', $competition) }}" class="text-teal-800 hover:underline">Daftar</a>
                            <a href="{{ route('coach.registrations.index', $competition) }}" class="ml-3 text-slate-600 hover:underline">Ringkasan</a>
                            @can('viewAny', App\Models\Competition::class)
                                <a href="{{ route('admin.registrations.index', $competition) }}" class="ml-3 text-slate-600 hover:underline">Verifikasi</a>
                                <a href="{{ route('admin.imports.index', $competition) }}" class="ml-3 text-slate-600 hover:underline">Import</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-slate-500">Tidak ada kejuaraan yang membuka pendaftaran.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
