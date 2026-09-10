@php
    $athlete = $state['athlete'] ?? [];
    $registrant = $state['registrant'] ?? [];
@endphp

@extends('layouts.public')

@section('title', 'Data pendaftar dan atlet')
@section('meta_description', 'Isi kontak pendaftar dan data atlet untuk '.$competition->name)

@section('content')
    <div class="mx-auto max-w-2xl">
        <p class="text-sm font-semibold uppercase tracking-wide text-teal-800">Pendaftaran</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $competition->name }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ $competition->venue }}, {{ $competition->city }}</p>

        @include('register._steps', ['current' => 1])

        <form method="POST" action="{{ route('register.athlete', $competition) }}" class="mt-6 space-y-5">
            @csrf

            <fieldset class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <legend class="px-1 text-base font-semibold text-slate-900">Siapa yang mendaftarkan?</legend>
                <p class="text-sm leading-6 text-slate-600">
                    Bisa orang tua, pelatih, atau atlet sendiri. Panitia menghubungi nomor WhatsApp ini jika data perlu diperbaiki.
                </p>

                <div>
                    <label for="registrant_name" class="block text-sm font-medium text-slate-800">Nama pendaftar</label>
                    <input id="registrant_name" name="registrant_name" required maxlength="100" autocomplete="name"
                        value="{{ old('registrant_name', $registrant['name'] ?? '') }}"
                        class="public-input" placeholder="Nama lengkap Anda">
                    @error('registrant_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="registrant_phone" class="block text-sm font-medium text-slate-800">Nomor WhatsApp</label>
                    <input id="registrant_phone" name="registrant_phone" required maxlength="20" inputmode="tel" autocomplete="tel"
                        value="{{ old('registrant_phone', $registrant['phone'] ?? '') }}"
                        class="public-input" placeholder="08…">
                    <p class="mt-1 text-xs leading-5 text-slate-500">Pakai nomor yang mudah dihubungi. Jangan nomor yang sudah tidak aktif.</p>
                    @error('registrant_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="registrant_email" class="block text-sm font-medium text-slate-800">Email <span class="font-normal text-slate-400">(opsional)</span></label>
                    <input id="registrant_email" name="registrant_email" type="email" maxlength="120" autocomplete="email"
                        value="{{ old('registrant_email', $registrant['email'] ?? '') }}"
                        class="public-input" placeholder="nama@email.com">
                    <p class="mt-1 text-xs leading-5 text-slate-500">Isi jika ingin menerima kabar verifikasi lewat email. Boleh dikosongkan.</p>
                    @error('registrant_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </fieldset>

            <fieldset class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <legend class="px-1 text-base font-semibold text-slate-900">Data atlet</legend>
                <p class="text-sm leading-6 text-slate-600">
                    Isi sesuai identitas. Tahun lahir menentukan kelompok umur — bukan umur di hari lomba.
                </p>

                <div>
                    <label for="full_name" class="block text-sm font-medium text-slate-800">Nama lengkap atlet</label>
                    <input id="full_name" name="full_name" required minlength="3" maxlength="100" autocomplete="off"
                        value="{{ old('full_name', $athlete['full_name'] ?? '') }}"
                        class="public-input" placeholder="Nama di buku acara">
                    @error('full_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="gender" class="block text-sm font-medium text-slate-800">Jenis kelamin</label>
                        <select id="gender" name="gender" required class="public-input">
                            <option value="">Pilih</option>
                            @foreach (App\Enums\Gender::cases() as $gender)
                                <option value="{{ $gender->value }}" @selected(old('gender', $athlete['gender'] ?? '') === $gender->value)>{{ $gender->label() }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Menyaring nomor putra atau putri yang boleh diikuti.</p>
                        @error('gender') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="birth_year" class="block text-sm font-medium text-slate-800">Tahun lahir</label>
                        <input id="birth_year" name="birth_year" type="number" required min="1950" max="{{ now()->year }}" inputmode="numeric"
                            value="{{ old('birth_year', $athlete['birth_year'] ?? '') }}"
                            class="public-input" placeholder="Contoh: 2016">
                        <p class="mt-1 text-xs leading-5 text-slate-500">Empat angka, misalnya 2016. Bukan tanggal lengkap.</p>
                        @error('birth_year') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="club_name" class="block text-sm font-medium text-slate-800">Nama klub atau sekolah</label>
                        <input id="club_name" name="club_name" required minlength="3" maxlength="100"
                            value="{{ old('club_name', $athlete['club_name'] ?? '') }}"
                            class="public-input"
                            placeholder="Ketik nama klub">
                        <p class="mt-1 text-xs leading-5 text-slate-500">Tidak perlu punya akun klub. Jika nama baru, panitia akan memeriksanya.</p>
                        @error('club_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="club_city" class="block text-sm font-medium text-slate-800">Kabupaten atau kota</label>
                        <input id="club_city" name="club_city" required maxlength="100"
                            value="{{ old('club_city', $athlete['club_city'] ?? '') }}"
                            class="public-input"
                            placeholder="Contoh: Padang">
                        <p class="mt-1 text-xs leading-5 text-slate-500">Ikut tercetak di buku acara.</p>
                        @error('club_city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </fieldset>

            <div class="hidden" aria-hidden="true">
                <label for="website">Website</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <button class="public-btn">Lanjut pilih nomor lomba</button>
        </form>
    </div>
@endsection
