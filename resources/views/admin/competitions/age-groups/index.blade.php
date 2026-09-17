@extends('layouts.app')

@section('title', 'Kelompok umur')

@section('content')
    <h1 class="text-2xl font-semibold">{{ $competition->name }}</h1>
    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'age-groups'])

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">Ubah data di tabel lalu Simpan. Rentang tahun lahir antar grup tidak boleh tumpang tindih. Label cetak (Romawi) tampil di kolom AGE buku acara. Tahun lahir terkunci jika grup sudah punya pendaftaran.</p>
        <form method="POST" action="{{ route('admin.competitions.age-groups.quick-fill', $competition) }}">
            @csrf
            <button class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Isi cepat 9 grup baku</button>
        </form>
    </div>

    <div class="mt-6 max-w-3xl rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="font-medium">Import Excel</h2>
        <p class="mt-1 text-sm leading-6 text-slate-600">
            Unduh template, sesuaikan nama (misalnya Searia1) dan tahun lahir, lalu unggah.
            Kode 1–9 tetap disarankan agar Excel nomor lomba bisa memakai nama itu.
            Grup yang sudah punya pendaftaran tidak boleh diubah tahun lahirnya.
        </p>
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            <a href="{{ route('admin.competitions.age-groups.template', $competition) }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-center text-sm hover:bg-slate-50">Unduh template Excel</a>
            <form method="POST" action="{{ route('admin.competitions.age-groups.import', $competition) }}" enctype="multipart/form-data" class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-end">
                @csrf
                <div class="flex-1">
                    <label for="age-group-file" class="block text-sm font-medium text-slate-700">Unggah .xlsx atau .csv</label>
                    <input id="age-group-file" type="file" name="file" accept=".xlsx,.csv" required class="mt-1 block w-full text-sm">
                    @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Unggah</button>
            </form>
        </div>
        @if (session('import_errors'))
            <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-amber-800">
                @foreach (session('import_errors') as $importError)
                    <li>{{ $importError }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    @php
        $editingId = old('editing_id');
        $adding = $editingId === null;
    @endphp

    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Kode</th>
                    <th class="px-4 py-3 font-medium">Nama</th>
                    <th class="px-4 py-3 font-medium">Label cetak</th>
                    <th class="px-4 py-3 font-medium">Tahun lahir</th>
                    <th class="px-4 py-3 font-medium">Urutan</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ageGroups as $group)
                    @php
                        $isEditing = (string) $editingId === (string) $group->id;
                        $yearsLocked = $group->registrations_count > 0;
                    @endphp
                    <tr class="border-t border-slate-100 align-top">
                        <td class="px-4 py-3">
                            <form id="edit-age-group-{{ $group->id }}" method="POST" action="{{ route('admin.competitions.age-groups.update', [$competition, $group]) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="editing_id" value="{{ $group->id }}">
                            </form>
                            <input form="edit-age-group-{{ $group->id }}" name="code" value="{{ $isEditing ? old('code', $group->code) : $group->code }}" required maxlength="10" aria-label="Kode {{ $group->name }}" class="w-20 rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                            @if ($isEditing)
                                @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <input form="edit-age-group-{{ $group->id }}" name="name" value="{{ $isEditing ? old('name', $group->name) : $group->name }}" required maxlength="50" aria-label="Nama {{ $group->name }}" class="w-full min-w-[8rem] rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                            @if ($isEditing)
                                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <input form="edit-age-group-{{ $group->id }}" name="display_code" value="{{ $isEditing ? old('display_code', $group->display_code) : $group->display_code }}" maxlength="10" placeholder="V" aria-label="Label cetak {{ $group->name }}" class="w-20 rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                            @if ($isEditing)
                                @error('display_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                <input form="edit-age-group-{{ $group->id }}" name="birth_year_start" type="number" value="{{ $isEditing ? old('birth_year_start', $group->birth_year_start) : $group->birth_year_start }}" required @readonly($yearsLocked) aria-label="Tahun lahir awal {{ $group->name }}" class="w-20 rounded-md border border-slate-300 px-2 py-1.5 text-sm {{ $yearsLocked ? 'bg-slate-100 text-slate-500' : '' }}">
                                <span class="text-slate-400">–</span>
                                <input form="edit-age-group-{{ $group->id }}" name="birth_year_end" type="number" value="{{ $isEditing ? old('birth_year_end', $group->birth_year_end) : $group->birth_year_end }}" required @readonly($yearsLocked) aria-label="Tahun lahir akhir {{ $group->name }}" class="w-20 rounded-md border border-slate-300 px-2 py-1.5 text-sm {{ $yearsLocked ? 'bg-slate-100 text-slate-500' : '' }}">
                            </div>
                            @if ($yearsLocked)
                                <p class="mt-1 text-xs text-slate-500">Terkunci karena sudah ada pendaftaran.</p>
                            @endif
                            @if ($isEditing)
                                @error('birth_year_start') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                @error('birth_year_end') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <input form="edit-age-group-{{ $group->id }}" name="sort_order" type="number" min="1" value="{{ $isEditing ? old('sort_order', $group->sort_order) : $group->sort_order }}" required aria-label="Urutan {{ $group->name }}" class="w-16 rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                            @if ($isEditing)
                                @error('sort_order') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-wrap items-center justify-end gap-3">
                                <button form="edit-age-group-{{ $group->id }}" class="text-teal-800 hover:underline">Simpan</button>
                                <form method="POST" action="{{ route('admin.competitions.age-groups.destroy', [$competition, $group]) }}" onsubmit="return confirm('Hapus kelompok umur ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-700 hover:underline">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada kelompok umur.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.pagination', ['paginator' => $ageGroups])

    <form method="POST" action="{{ route('admin.competitions.age-groups.store', $competition) }}" class="mt-6 max-w-3xl space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        @csrf
        <h2 class="font-medium">Tambah kelompok umur</h2>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-slate-700">Kode</label>
                <input name="code" value="{{ $adding ? old('code') : '' }}" required maxlength="10" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @if ($adding)
                    @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Nama</label>
                <input name="name" value="{{ $adding ? old('name') : '' }}" required maxlength="50" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Label cetak</label>
                <input name="display_code" value="{{ $adding ? old('display_code') : '' }}" maxlength="10" placeholder="V" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-slate-700">Tahun lahir awal</label>
                <input name="birth_year_start" type="number" value="{{ $adding ? old('birth_year_start') : '' }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tahun lahir akhir</label>
                <input name="birth_year_end" type="number" value="{{ $adding ? old('birth_year_end') : '' }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @if ($adding)
                    @error('birth_year_end') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Urutan</label>
                <input name="sort_order" type="number" min="1" value="{{ $adding ? old('sort_order', ((int) $competition->ageGroups()->max('sort_order')) + 1) : ((int) $competition->ageGroups()->max('sort_order')) + 1 }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Tambah grup</button>
    </form>
@endsection
