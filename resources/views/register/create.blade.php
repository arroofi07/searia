@php
    $athlete = $state['athlete'] ?? [];
@endphp

@extends('layouts.public')

@section('title', 'Data peserta')
@section('meta_description', 'Isi data peserta sesuai kolom pendaftaran untuk '.$competition->name)

@section('content')
    @include('public._mast', [
        'kicker' => 'Pendaftaran · langkah 1',
        'title' => $competition->name,
        'meta' => $competition->venue.', '.$competition->city,
    ])

    @include('register._steps', ['current' => 1])

    <form method="POST" action="{{ route('register.athlete', $competition) }}">
        @csrf

        <fieldset>
            <legend>Data peserta</legend>
            <p class="lead">Isi sama seperti di berkas Excel panitia. Tahun lahir menentukan kelompok umur — bukan umur di hari lomba.</p>

            <div class="gap-top">
                <label for="full_name">Nama lengkap</label>
                <input id="full_name" name="full_name" required minlength="3" maxlength="100" autocomplete="off"
                    value="{{ old('full_name', $athlete['full_name'] ?? '') }}"
                    class="public-input" placeholder="Nama di buku acara">
                @error('full_name') <p class="note">{{ $message }}</p> @enderror
            </div>

            <div class="facts">
                <div>
                    <label for="gender">L/P</label>
                    <select id="gender" name="gender" required class="public-input">
                        <option value="">Pilih</option>
                        @foreach (App\Enums\Gender::cases() as $gender)
                            <option value="{{ $gender->value }}" @selected(old('gender', $athlete['gender'] ?? '') === $gender->value)>{{ $gender->formLabel() }}</option>
                        @endforeach
                    </select>
                    <p class="meta">L = putra, P = putri. Menyaring kode acara yang boleh diikuti.</p>
                    @error('gender') <p class="note">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="birth_year">Tahun lahir</label>
                    <input id="birth_year" name="birth_year" type="number" required min="1950" max="{{ now()->year }}" inputmode="numeric"
                        value="{{ old('birth_year', $athlete['birth_year'] ?? '') }}"
                        class="public-input" placeholder="Contoh: 2016">
                    <p class="meta">Empat angka, misalnya 2016. Bukan tanggal lengkap.</p>
                    @error('birth_year') <p class="note">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="club_name">Klub/sekolah</label>
                    <input id="club_name" name="club_name" required minlength="3" maxlength="100"
                        value="{{ old('club_name', $athlete['club_name'] ?? '') }}"
                        class="public-input"
                        placeholder="Ketik nama klub atau sekolah">
                    <p class="meta">Tidak perlu punya akun. Jika nama baru, panitia akan memeriksanya.</p>
                    @error('club_name') <p class="note">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="club_city">Kabupaten/kota</label>
                    <input id="club_city" name="club_city" required maxlength="100"
                        value="{{ old('club_city', $athlete['club_city'] ?? '') }}"
                        class="public-input"
                        placeholder="Contoh: Padang">
                    <p class="meta">Ikut tercetak di buku acara.</p>
                    @error('club_city') <p class="note">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        <div class="hidden" aria-hidden="true">
            <label for="website">Website</label>
            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
        </div>

        <div class="gap-top">
            <button class="public-btn">Lanjut pilih kode acara</button>
        </div>
    </form>
@endsection
