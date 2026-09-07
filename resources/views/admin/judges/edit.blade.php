@extends('layouts.app')

@section('title', 'Penugasan juri')

@section('content')
    <h1 class="text-2xl font-semibold">Penugasan juri</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }}</p>

    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'judges'])

    <form method="POST" action="{{ route('admin.judges.update', $competition) }}" class="mt-6 space-y-4">
        @csrf
        @method('PUT')

        @foreach ($events as $event)
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="font-medium">Acara {{ $event->event_number }} · {{ $event->formattedName() }}</h2>
                <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($judges as $judge)
                        <label class="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm">
                            <input
                                type="checkbox"
                                name="assignments[{{ $event->id }}][]"
                                value="{{ $judge->id }}"
                                class="rounded border-slate-300"
                                @checked($event->judges->contains('id', $judge->id))
                            >
                            <span>{{ $judge->name }} <span class="text-slate-400">({{ $judge->role->value }})</span></span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach

        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Simpan penugasan</button>
    </form>
@endsection
