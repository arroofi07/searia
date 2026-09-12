@extends('layouts.app')

@section('title', 'Import peserta dari Excel')

@section('content')
    <h1 class="text-2xl font-semibold">Import peserta dari Excel</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }} · jalur panitia dan Super Admin. Peserta perorangan memakai form publik.</p>

    @include('admin.registrations._tabs', ['competition' => $competition, 'current' => 'imports'])

    <div class="mt-6 max-w-3xl rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-700">
        <p>Unduh template, isi lembar <strong>PESERTA</strong>, lalu unggah di sini. Satu baris = satu atlet pada satu nomor lomba.</p>
        <p class="mt-2">Lembar <strong>NOMOR LOMBA</strong> terkunci, hanya rujukan. Salin <strong>KODE ACARA</strong> ke PESERTA. Jangan ubah NOMOR PERLOMBAAN, GENDER, atau GRUP YANG BOLEH IKUT — grup diubah di Matriks kelayakan di aplikasi.</p>
    </div>

    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('admin.imports.template', $competition) }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Unduh template Excel</a>
    </div>

    @if ($competition->isOpenForRegistration())
        <form method="POST" action="{{ route('admin.imports.store', $competition) }}" enctype="multipart/form-data" class="mt-6 max-w-xl rounded-lg border border-slate-200 bg-white p-5">
            @csrf
            <label for="file" class="block text-sm font-medium text-slate-700">Unggah .xlsx atau .csv</label>
            <input id="file" type="file" name="file" accept=".xlsx,.csv" required class="mt-2 block w-full text-sm">
            @error('file') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <p class="mt-2 text-xs text-slate-500">Maksimal 5 MB dan 2.000 baris. Berkas di atas 200 baris divalidasi di latar belakang.</p>
            <button class="mt-4 rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Unggah dan validasi</button>
        </form>
    @else
        <div class="mt-6 max-w-xl rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Unggah Excel hanya saat status kejuaraan <strong>Pendaftaran terbuka</strong>. Status saat ini: {{ $competition->status->label() }}.
            <a href="{{ route('admin.competitions.show', $competition) }}" class="mt-1 block font-medium text-teal-800 hover:underline">Kembali ke ringkasan untuk mengubah status</a>
        </div>
    @endif

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
