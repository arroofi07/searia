@extends('layouts.app')

@section('title', 'Import peserta')

@section('content')
    <h1 class="text-2xl font-semibold">Import peserta</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }}</p>

    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'imports'])

    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('admin.imports.template', $competition) }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Unduh template Excel</a>
    </div>

    <form method="POST" action="{{ route('admin.imports.store', $competition) }}" enctype="multipart/form-data" class="mt-6 max-w-xl rounded-lg border border-slate-200 bg-white p-5">
        @csrf
        <label for="file" class="block text-sm font-medium text-slate-700">Unggah .xlsx atau .csv</label>
        <input id="file" type="file" name="file" accept=".xlsx,.csv" required class="mt-2 block w-full text-sm">
        @error('file') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        <p class="mt-2 text-xs text-slate-500">Maksimal 5 MB dan 2.000 baris. Berkas di atas 200 baris divalidasi di latar belakang.</p>
        <button class="mt-4 rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Unggah dan validasi</button>
    </form>

    <div class="mt-8 overflow-x-auto rounded-lg border border-slate-200 bg-white">
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
                        <td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada unggahan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
