@extends('layouts.app')

@section('title', 'Kelompok umur')

@section('content')
    <h1 class="text-2xl font-semibold">{{ $competition->name }}</h1>
    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'age-groups'])

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">Rentang tahun lahir antar grup tidak boleh tumpang tindih. Label cetak (Romawi) tampil di kolom AGE buku acara.</p>
        <form method="POST" action="{{ route('admin.competitions.age-groups.quick-fill', $competition) }}">
            @csrf
            <button class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Isi cepat 6 grup baku</button>
        </form>
    </div>

    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Kode</th>
                    <th class="px-4 py-3 font-medium">Nama</th>
                    <th class="px-4 py-3 font-medium">Label cetak</th>
                    <th class="px-4 py-3 font-medium">Tahun lahir</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($competition->ageGroups as $group)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">{{ $group->code }}</td>
                        <td class="px-4 py-3">{{ $group->name }}</td>
                        <td class="px-4 py-3">{{ $group->display_code ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $group->birth_year_start }}–{{ $group->birth_year_end }}</td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.competitions.age-groups.destroy', [$competition, $group]) }}" onsubmit="return confirm('Hapus kelompok umur ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-700 hover:underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada kelompok umur.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.competitions.age-groups.store', $competition) }}" class="mt-6 max-w-3xl space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        @csrf
        <h2 class="font-medium">Tambah kelompok umur</h2>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-slate-700">Kode</label>
                <input name="code" value="{{ old('code') }}" required maxlength="10" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Nama</label>
                <input name="name" value="{{ old('name') }}" required maxlength="50" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Label cetak</label>
                <input name="display_code" value="{{ old('display_code') }}" maxlength="10" placeholder="V" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-slate-700">Tahun lahir awal</label>
                <input name="birth_year_start" type="number" value="{{ old('birth_year_start') }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tahun lahir akhir</label>
                <input name="birth_year_end" type="number" value="{{ old('birth_year_end') }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('birth_year_end') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Urutan</label>
                <input name="sort_order" type="number" min="1" value="{{ old('sort_order', $competition->ageGroups->count() + 1) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Tambah grup</button>
    </form>
@endsection
