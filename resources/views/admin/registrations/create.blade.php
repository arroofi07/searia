@extends('layouts.app')

@section('title', 'Tambah pendaftaran manual')

@section('content')
    <h1 class="text-2xl font-semibold">Tambah pendaftaran manual</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }}</p>

    @if ($errors->any())
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.registrations.store', $competition) }}" class="mt-6 max-w-xl space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700">Atlet</label>
            <select name="athlete_id" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Pilih atlet</option>
                @foreach ($athletes as $athlete)
                    <option value="{{ $athlete->id }}" @selected(old('athlete_id') == $athlete->id)>
                        {{ $athlete->full_name }} · {{ $athlete->birth_year }} · {{ $athlete->club?->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Nomor lomba</label>
            <select name="event_id" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Pilih nomor</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected(old('event_id') == $event->id)>
                        {{ $event->event_number }} · {{ $event->formattedName() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Seed time (opsional)</label>
            <input type="text" name="seed_time" value="{{ old('seed_time') }}" placeholder="mm:ss.SS atau kosong = NT"
                class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="verify_now" value="1" @checked(old('verify_now', true)) class="h-4 w-4">
            Langsung verifikasi (siap seeding)
        </label>
        <div class="flex flex-wrap gap-3">
            <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Simpan</button>
            <a href="{{ route('admin.registrations.index', $competition) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Batal</a>
        </div>
    </form>
@endsection
