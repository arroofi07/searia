@extends('layouts.app')

@section('title', 'Tambah akun')

@section('content')
    <div>
        <h1 class="text-2xl font-semibold">Tambah akun</h1>
        <p class="mt-1 text-sm text-slate-500">Buat akun Panitia atau Juri untuk masuk ke dasbor.</p>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}" class="mt-6 max-w-xl space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        @csrf
        @include('admin.users._form', [
            'user' => null,
            'roles' => $roles,
            'defaultRole' => $defaultRole,
        ])
        <div class="flex gap-3 pt-2">
            <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Simpan</button>
            <a href="{{ route('admin.users.index') }}" class="rounded-md px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Batal</a>
        </div>
    </form>
@endsection
