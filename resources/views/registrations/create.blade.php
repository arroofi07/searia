@extends('layouts.app')

@section('title', 'Pilih atlet')

@section('content')
    <h1 class="text-2xl font-semibold">{{ $competition->name }}</h1>
    <p class="mt-1 text-sm text-slate-500">Langkah 1 dari 3 · Pilih atlet</p>

    <form method="POST" action="{{ route('registrations.athlete', $competition) }}" class="mt-6 max-w-xl space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        @csrf
        <div>
            <label for="athlete_id" class="block text-sm font-medium text-slate-700">Atlet</label>
            <select id="athlete_id" name="athlete_id" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Pilih atlet</option>
                @foreach ($athletes as $athlete)
                    <option value="{{ $athlete->id }}"
                        data-year="{{ $athlete->birth_year }}"
                        @selected((string) old('athlete_id', $selected?->id) === (string) $athlete->id)>
                        {{ $athlete->full_name }} · {{ $athlete->birth_year }} · {{ $athlete->club->name }}
                    </option>
                @endforeach
            </select>
            @error('athlete_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <p id="age-group-hint" class="text-sm text-slate-600">
            @if ($selected && $ageGroup)
                Tahun lahir {{ $selected->birth_year }} → {{ $ageGroup->name }}
            @elseif ($selected && ! $ageGroup)
                Usia atlet di luar rentang kejuaraan ini
            @endif
        </p>

        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Lanjut pilih nomor</button>
    </form>
@endsection
