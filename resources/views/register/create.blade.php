@php
    $athlete = $state['athlete'] ?? [];
    $registrant = $state['registrant'] ?? [];
@endphp

@extends('layouts.public')

@section('title', 'Data pendaftar dan atlet')

@section('content')
    <h1 class="text-2xl font-semibold">{{ $competition->name }}</h1>
    <p class="mt-1 text-sm text-slate-500">Langkah 1 dari 3 · Data pendaftar dan atlet</p>

    <form method="POST" action="{{ route('register.athlete', $competition) }}" class="mt-6 max-w-2xl space-y-6">
        @csrf

        <fieldset class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
            <legend class="px-1 text-sm font-semibold text-slate-700">Kontak pendaftar</legend>
            <p class="text-sm text-slate-500">Panitia menghubungi nomor ini bila ada data yang perlu diperbaiki.</p>

            <div>
                <label for="registrant_name" class="block text-sm font-medium text-slate-700">Nama pendaftar</label>
                <input id="registrant_name" name="registrant_name" required maxlength="100"
                    value="{{ old('registrant_name', $registrant['name'] ?? '') }}"
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('registrant_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="registrant_phone" class="block text-sm font-medium text-slate-700">Nomor WhatsApp</label>
                <input id="registrant_phone" name="registrant_phone" required maxlength="20" inputmode="tel"
                    value="{{ old('registrant_phone', $registrant['phone'] ?? '') }}"
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('registrant_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="registrant_email" class="block text-sm font-medium text-slate-700">Email <span class="text-slate-400">(opsional)</span></label>
                <input id="registrant_email" name="registrant_email" type="email" maxlength="120"
                    value="{{ old('registrant_email', $registrant['email'] ?? '') }}"
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">Diisi bila Anda ingin menerima pemberitahuan hasil verifikasi lewat email.</p>
                @error('registrant_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </fieldset>

        <fieldset class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
            <legend class="px-1 text-sm font-semibold text-slate-700">Data atlet</legend>

            <div>
                <label for="full_name" class="block text-sm font-medium text-slate-700">Nama lengkap</label>
                <input id="full_name" name="full_name" required minlength="3" maxlength="100"
                    value="{{ old('full_name', $athlete['full_name'] ?? '') }}"
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('full_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="gender" class="block text-sm font-medium text-slate-700">Jenis kelamin</label>
                    <select id="gender" name="gender" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih</option>
                        @foreach (App\Enums\Gender::cases() as $gender)
                            <option value="{{ $gender->value }}" @selected(old('gender', $athlete['gender'] ?? '') === $gender->value)>{{ $gender->label() }}</option>
                        @endforeach
                    </select>
                    @error('gender') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="birth_year" class="block text-sm font-medium text-slate-700">Tahun lahir</label>
                    <input id="birth_year" name="birth_year" type="number" required min="1950" max="{{ now()->year }}"
                        value="{{ old('birth_year', $athlete['birth_year'] ?? '') }}"
                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @error('birth_year') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="club_name" class="block text-sm font-medium text-slate-700">Nama klub atau sekolah</label>
                    <input id="club_name" name="club_name" required minlength="3" maxlength="100"
                        value="{{ old('club_name', $athlete['club_name'] ?? '') }}"
                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                        placeholder="Ketik nama klub">
                    <p class="mt-1 text-xs text-slate-500">Diisi sendiri oleh pendaftar. Bila klub baru, panitia akan memverifikasinya.</p>
                    @error('club_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="club_city" class="block text-sm font-medium text-slate-700">Kabupaten atau kota</label>
                    <input id="club_city" name="club_city" required maxlength="100"
                        value="{{ old('club_city', $athlete['club_city'] ?? '') }}"
                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                        placeholder="Contoh: Padang">
                    <p class="mt-1 text-xs text-slate-500">Ikut tercetak di buku acara.</p>
                    @error('club_city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        <div class="hidden" aria-hidden="true">
            <label for="website">Website</label>
            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
        </div>

        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Lanjut pilih nomor lomba</button>
    </form>
@endsection
