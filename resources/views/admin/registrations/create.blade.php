@extends('layouts.app')

@section('title', 'Tambah pendaftaran manual')

@section('content')
    <h1 class="text-2xl font-semibold">Tambah pendaftaran manual</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }}</p>

    @include('admin.registrations._tabs', ['competition' => $competition, 'current' => 'create'])

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
            <label for="age_group_id" class="block text-sm font-medium text-slate-700">Naik kelas (opsional)</label>
            <select id="age_group_id" name="age_group_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Grup sesuai tahun lahir</option>
                @foreach ($ageGroups as $group)
                    <option value="{{ $group->id }}" @selected((string) old('age_group_id') === (string) $group->id)>
                        {{ $group->name }} · {{ $group->birth_year_start }}–{{ $group->birth_year_end }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">Hanya grup lebih tua (tahun lahir lebih awal). Tahun lahir atlet tidak diubah. Turun kelas ditolak.</p>
            @error('age_group_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="override_reason" class="block text-sm font-medium text-slate-700">Alasan naik kelas</label>
            <input id="override_reason" type="text" name="override_reason" value="{{ old('override_reason') }}" maxlength="500"
                placeholder="Wajib diisi jika naik kelas"
                class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @error('override_reason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Catatan waktu (opsional)</label>
            <input type="text" name="seed_time" value="{{ old('seed_time') }}" placeholder="013470 atau 00:52.20 · kosong = NT"
                class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-slate-500">Waktu terbaik atlet untuk membagi seri dan lintasan, bukan hasil lomba. Kosong = NT.</p>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="verify_now" value="1" @checked(old('verify_now', true)) class="h-4 w-4">
            Langsung setujui (ikut pembagian seri)
        </label>
        <div class="flex flex-wrap gap-3">
            <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Simpan</button>
            <a href="{{ route('admin.registrations.index', $competition) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Batal</a>
        </div>
    </form>
@endsection
