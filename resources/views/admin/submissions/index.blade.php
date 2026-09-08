@extends('layouts.app')

@section('title', 'Pendaftaran masuk')

@section('content')
    <h1 class="text-2xl font-semibold">Pendaftaran masuk</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }}</p>

    @include('admin.registrations._tabs', ['competition' => $competition, 'current' => 'submissions'])

    <form method="GET" class="mt-4 flex flex-wrap gap-3">
        <input name="code" value="{{ $filters['code'] ?? '' }}" placeholder="Kode pendaftaran"
            class="rounded-md border border-slate-300 px-3 py-2 text-sm uppercase">
        <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama pendaftar, atlet, atau nomor telepon"
            class="w-72 rounded-md border border-slate-300 px-3 py-2 text-sm">
        <button class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Cari</button>
    </form>

    <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Kode</th>
                    <th class="px-4 py-3 font-medium">Atlet</th>
                    <th class="px-4 py-3 font-medium">Pendaftar</th>
                    <th class="px-4 py-3 font-medium">Entri</th>
                    <th class="px-4 py-3 font-medium">Masuk</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($submissions as $submission)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-mono">
                            <a href="{{ route('admin.submissions.show', $submission) }}" class="text-teal-800 hover:underline">{{ $submission->code }}</a>
                        </td>
                        <td class="px-4 py-3">
                            {{ $submission->athlete?->full_name }}
                            <span class="block text-xs text-slate-500">{{ $submission->athlete?->club?->name }}</span>
                        </td>
                        <td class="px-4 py-3">
                            {{ $submission->registrant_name }}
                            <span class="block text-xs text-slate-500">{{ $submission->registrant_phone }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $submission->registrations->count() }} nomor</td>
                        <td class="px-4 py-3 text-slate-500">{{ $submission->created_at->translatedFormat('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada pendaftaran yang masuk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $submissions->links() }}</div>
@endsection
