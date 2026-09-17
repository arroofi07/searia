@extends('layouts.app')

@section('title', 'Import Excel')

@section('content')
    <h1 class="text-2xl font-semibold">Import Excel</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }} · satu berkas untuk nomor lomba dan peserta.</p>

    @include('admin.registrations._tabs', ['competition' => $competition, 'current' => 'imports'])

    <div class="mt-6">
        @include('admin.imports._instructions')
    </div>

    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('admin.imports.template', $competition) }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Unduh template Excel</a>
    </div>

    @if ($competition->isOpenForRegistration() || auth()->user()?->can('update', $competition))
        <form method="POST" action="{{ route('admin.imports.store', $competition) }}" enctype="multipart/form-data" class="mt-6 max-w-xl rounded-lg border border-slate-200 bg-white p-5">
            @csrf
            <label for="file" class="block text-sm font-medium text-slate-700">Unggah .xlsx atau .csv</label>
            <input id="file" type="file" name="file" accept=".xlsx,.csv" required class="mt-2 block w-full text-sm">
            @error('file') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <p class="mt-2 text-xs leading-5 text-slate-500">
                Maksimal 5 MB dan 2.000 baris peserta.
                Satu unggahan mengimpor <strong>nomor lomba + peserta</strong> sekaligus.
                Jika acara masih draf, status otomatis dibuka ke <strong>Pendaftaran terbuka</strong>.
                Baris peserta yang valid langsung disimpan; baris bermasalah tetap bisa diperbaiki di pratinjau.
            </p>
            <button class="mt-4 rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Unggah dan proses</button>
        </form>
    @else
        <div class="mt-6 max-w-xl rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Unggah Excel memerlukan izin mengelola acara ini.
        </div>
    @endif

    <div class="mt-8 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <h2 class="border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-900">Riwayat unggahan peserta</h2>
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Berkas</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Baris</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($batches as $batch)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">{{ $batch->original_filename }}</td>
                        <td class="px-4 py-3">{{ $batch->status->label() }}</td>
                        <td class="px-4 py-3">{{ $batch->valid_rows }}/{{ $batch->total_rows }} valid</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.imports.show', $batch) }}" class="text-teal-800 hover:underline">Pratinjau</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada unggahan peserta.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.pagination', ['paginator' => $batches])
@endsection
