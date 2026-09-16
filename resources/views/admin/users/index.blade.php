@extends('layouts.app')

@section('title', 'Akun panitia dan juri')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Akun panitia dan juri</h1>
            <p class="text-sm text-slate-500">Buat dan kelola akun internal untuk panitia serta juri.</p>
        </div>
        @can('create', App\Models\User::class)
            <a href="{{ route('admin.users.create') }}" class="rounded-md bg-teal-700 px-4 py-2 text-center text-sm font-medium text-white hover:bg-teal-800">Tambah akun</a>
        @endcan
    </div>

    @if ($errors->has('delete') || $errors->has('is_active'))
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $errors->first('delete') ?: $errors->first('is_active') }}
        </div>
    @endif

    <form method="GET" class="mt-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-4">
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama atau email"
            class="rounded-md border border-slate-300 px-3 py-2 text-sm sm:col-span-2">
        <select name="role" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua peran</option>
            @foreach ($roles as $role)
                <option value="{{ $role->value }}" @selected(($filters['role'] ?? '') === $role->value)>{{ $role->label() }}</option>
            @endforeach
        </select>
        <select name="is_active" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua status</option>
            <option value="1" @selected(($filters['is_active'] ?? '') === '1')>Aktif</option>
            <option value="0" @selected(($filters['is_active'] ?? '') === '0')>Nonaktif</option>
        </select>
        <div class="flex gap-2 sm:col-span-4">
            <button class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white">Saring</button>
            <a href="{{ route('admin.users.index') }}" class="rounded-md px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Reset</a>
        </div>
    </form>

    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Nama</th>
                    <th class="px-4 py-3 font-medium">Email</th>
                    <th class="px-4 py-3 font-medium">Peran</th>
                    <th class="px-4 py-3 font-medium">Aktif</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $account)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-medium text-slate-900">
                            {{ $account->name }}
                            @if ($account->phone)
                                <div class="text-xs font-normal text-slate-500">{{ $account->phone }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $account->email }}</td>
                        <td class="px-4 py-3">{{ $account->role->label() }}</td>
                        <td class="px-4 py-3">{{ $account->is_active ? 'Ya' : 'Tidak' }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('update', $account)
                                <a href="{{ route('admin.users.edit', $account) }}" class="font-medium text-teal-800 hover:underline">Ubah</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada akun.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.pagination', ['paginator' => $users])
@endsection
