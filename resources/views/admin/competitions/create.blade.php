@extends('layouts.app')

@section('title', 'Tambah kejuaraan')

@section('content')
    <h1 class="text-2xl font-semibold">Tambah kejuaraan</h1>

    <form method="POST" action="{{ route('admin.competitions.store') }}" class="mt-6 max-w-3xl space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        @csrf
        @include('admin.competitions._form', ['competition' => null])
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Simpan</button>
    </form>
@endsection
