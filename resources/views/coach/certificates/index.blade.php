@extends('layouts.app')

@section('title', 'Sertifikat klub · '.$competition->name)

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Sertifikat · {{ $club?->name }}</h1>
            <p class="text-sm text-slate-500">{{ $competition->name }}</p>
        </div>
        <form method="POST" action="{{ route('certificates.archive', $competition) }}">
            @csrf
            <input type="hidden" name="club_id" value="{{ $club?->id }}">
            <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Unduh arsip ZIP klub</button>
        </form>
    </div>

    @if (session('status'))
        <p class="mt-4 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">{{ session('status') }}</p>
    @endif

    <table class="mt-6 min-w-full text-left text-sm">
        <thead class="text-slate-500">
            <tr>
                <th class="py-2 pr-3">Jenis</th>
                <th class="py-2 pr-3">Atlet</th>
                <th class="py-2 pr-3">Acara</th>
                <th class="py-2">Unduh</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($certificates as $certificate)
                <tr class="border-t border-slate-100">
                    <td class="py-2 pr-3">{{ $certificate->type === 'winner' ? 'Juara #'.$certificate->rank : 'Peserta' }}</td>
                    <td class="py-2 pr-3">{{ $certificate->athlete?->full_name }}</td>
                    <td class="py-2 pr-3">{{ $certificate->event?->event_number }} · {{ $certificate->event?->formattedName() }}</td>
                    <td class="py-2">
                        <a href="{{ route('certificates.download', $certificate) }}" class="text-teal-800 hover:underline">PDF</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-6 text-slate-500">Belum ada sertifikat.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
