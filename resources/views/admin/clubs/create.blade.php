@extends('layouts.app')

@section('title', 'Tambah klub')

@section('content')
    <h1 class="text-2xl font-semibold">Tambah klub</h1>

    <form method="POST" action="{{ route('admin.clubs.store') }}" class="mt-6 max-w-xl space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        @csrf
        @include('admin.clubs._form', ['club' => null])
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Simpan</button>
    </form>
@endsection
