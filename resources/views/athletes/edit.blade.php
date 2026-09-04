@extends('layouts.app')

@section('title', 'Ubah atlet')

@section('content')
    <h1 class="text-2xl font-semibold">Ubah atlet</h1>

    <form method="POST" action="{{ route('athletes.update', $athlete) }}" enctype="multipart/form-data" class="mt-6 max-w-xl space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        @csrf
        @method('PUT')
        @include('athletes._form', ['athlete' => $athlete, 'clubs' => $clubs, 'lockedClub' => $lockedClub])
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Simpan perubahan</button>
    </form>
@endsection
