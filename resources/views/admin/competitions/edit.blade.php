@extends('layouts.app')

@section('title', 'Ubah kejuaraan')

@section('content')
    <h1 class="text-2xl font-semibold">Ubah kejuaraan</h1>
    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'edit'])

    <form method="POST" action="{{ route('admin.competitions.update', $competition) }}" class="mt-6 max-w-3xl space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        @csrf
        @method('PUT')
        @include('admin.competitions._form', ['competition' => $competition])
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Simpan perubahan</button>
    </form>
@endsection
