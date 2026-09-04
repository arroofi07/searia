@extends('layouts.app')

@section('title', 'Ubah profil klub')

@section('content')
    <h1 class="text-2xl font-semibold">Ubah profil klub</h1>
    <p class="mt-1 text-sm text-slate-500">Perubahan nama setelah klub terverifikasi akan dikirim ulang ke panitia. Status klub tidak dapat diubah sendiri.</p>

    <form method="POST" action="{{ route('coach.club.update', $club) }}" enctype="multipart/form-data" class="mt-6 max-w-xl space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        @csrf
        @method('PUT')
        @include('admin.clubs._form', ['club' => $club])

        <div>
            <label for="logo" class="block text-sm font-medium text-slate-700">Logo (JPG atau PNG, maks. 2 MB)</label>
            <input id="logo" name="logo" type="file" accept="image/jpeg,image/png" class="mt-1 block w-full text-sm">
            @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Simpan profil</button>
    </form>
@endsection
