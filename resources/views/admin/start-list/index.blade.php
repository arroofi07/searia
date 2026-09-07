@extends('layouts.app')

@section('title', 'Cetak buku acara')

@section('content')
    <h1 class="text-2xl font-semibold">Cetak buku acara</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }}</p>

    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'start-list'])

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <form method="GET" action="{{ route('admin.start-list.pdf', $competition) }}" class="rounded-lg border border-slate-200 bg-white p-5 space-y-3">
            <h2 class="font-medium">Buku acara PDF</h2>
            <div>
                <label class="block text-sm text-slate-600">Sesi</label>
                <select name="session" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Seluruh sesi</option>
                    @foreach ($sessions as $session)
                        <option value="{{ $session }}">Sesi {{ $session }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-slate-600">Nomor acara</label>
                <select name="event_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Seluruh nomor</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}">{{ $event->event_number }} {{ $event->formattedName() }}</option>
                    @endforeach
                </select>
            </div>
            <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Unduh buku acara</button>
        </form>

        <form method="GET" action="{{ route('admin.start-list.results', $competition) }}" class="rounded-lg border border-slate-200 bg-white p-5 space-y-3">
            <h2 class="font-medium">Lembar hasil kosong</h2>
            <p class="text-sm text-slate-500">Satu halaman per seri, dengan kolom waktu dan tanda tangan juri.</p>
            <div>
                <label class="block text-sm text-slate-600">Sesi</label>
                <select name="session" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Seluruh sesi</option>
                    @foreach ($sessions as $session)
                        <option value="{{ $session }}">Sesi {{ $session }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-slate-600">Nomor acara</label>
                <select name="event_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Seluruh nomor</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}">{{ $event->event_number }} {{ $event->formattedName() }}</option>
                    @endforeach
                </select>
            </div>
            <button class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Unduh lembar hasil</button>
        </form>
    </div>

    <p class="mt-6 text-sm">
        <a href="{{ route('start-list.show', $competition) }}" class="text-teal-800 hover:underline" target="_blank" rel="noopener">Buka tampilan publik</a>
    </p>
@endsection
