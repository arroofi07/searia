@extends('layouts.app')

@section('title', 'Ubah akun')

@section('content')
    <div>
        <h1 class="text-2xl font-semibold">Ubah akun</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $user->email }}</p>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-6 max-w-xl space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        @csrf
        @method('PUT')
        @include('admin.users._form', [
            'user' => $user,
            'roles' => $roles,
            'defaultRole' => null,
        ])
        <div class="flex flex-wrap gap-3 pt-2">
            <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Simpan perubahan</button>
            <a href="{{ route('admin.users.index') }}" class="rounded-md px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Batal</a>
        </div>
    </form>

    @can('delete', $user)
        @if ($user->is_active)
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="mt-4 max-w-xl" onsubmit="return confirm('Nonaktifkan akun ini?')">
                @csrf
                @method('DELETE')
                <button class="rounded-md border border-red-200 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Nonaktifkan akun</button>
            </form>
        @endif
    @endcan
@endsection
