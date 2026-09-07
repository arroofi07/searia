@extends('layouts.app')

@section('title', 'Hasil lomba')

@section('content')
    <h1 class="text-2xl font-semibold">Hasil lomba</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }}</p>

    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'results'])

    <div class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Acara</th>
                    <th class="px-4 py-3">Seri dikunci</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $event)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">{{ $event->event_number }} {{ $event->formattedName() }}</td>
                        <td class="px-4 py-3">{{ $event->locked_heats_count }} / {{ $event->heats_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.results.show', [$competition, $event]) }}" class="text-teal-800 hover:underline">Buka</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-slate-500">Belum ada nomor lomba.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="mt-4 text-sm flex flex-wrap gap-4">
        <a href="{{ route('admin.judges.edit', $competition) }}" class="text-teal-800 hover:underline">Kelola penugasan juri</a>
        <a href="{{ route('admin.results.verify', $competition) }}" class="text-teal-800 hover:underline">Verifikasi hasil</a>
        <a href="{{ route('results.index', $competition) }}" class="text-teal-800 hover:underline">Pratinjau publik</a>
    </p>
@endsection
