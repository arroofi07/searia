@extends('layouts.app')

@section('title', 'Gabungkan atlet')

@section('content')
    <h1 class="text-2xl font-semibold">Gabungkan atlet</h1>
    <p class="mt-1 text-sm text-slate-500">
        Atlet yang dipertahankan: <strong>{{ $athlete->full_name }}</strong> ({{ $athlete->birth_year }}, {{ $athlete->club->name }}).
        Seluruh pendaftaran atlet yang dipilih akan dipindahkan ke sini. Penggabungan tidak dijalankan otomatis.
    </p>

    @if ($similarAthletes->isNotEmpty())
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm">
            <p class="font-medium">Kandidat mirip:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($similarAthletes as $similar)
                    <li>{{ $similar->full_name }} · {{ $similar->birth_year }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.athletes.merge.store', $athlete) }}" class="mt-6 max-w-xl space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        @csrf
        <div>
            <label for="discard_athlete_id" class="block text-sm font-medium text-slate-700">Atlet yang digabung (akan dihapus)</label>
            <select id="discard_athlete_id" name="discard_athlete_id" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Pilih atlet</option>
                @foreach ($candidates as $candidate)
                    <option value="{{ $candidate->id }}" @selected((string) old('discard_athlete_id') === (string) $candidate->id)>
                        {{ $candidate->full_name }} · {{ $candidate->birth_year }} · {{ $candidate->gender->label() }}
                    </option>
                @endforeach
            </select>
            @error('discard_athlete_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Gabungkan</button>
    </form>
@endsection
