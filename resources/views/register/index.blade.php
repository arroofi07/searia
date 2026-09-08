@extends('layouts.public')

@section('title', 'Pendaftaran peserta')
@section('meta_description', 'Daftarkan atlet ke kejuaraan renang yang sedang membuka pendaftaran. Tanpa perlu membuat akun.')

@section('content')
    <h1 class="text-2xl font-semibold">Pendaftaran peserta</h1>
    <p class="mt-1 max-w-2xl text-sm text-slate-600">
        Pilih kejuaraan yang sedang membuka pendaftaran. Anda tidak perlu membuat akun — cukup isi tiga langkah,
        lalu simpan kode pendaftaran yang muncul di akhir.
    </p>

    <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Kejuaraan</th>
                    <th class="px-4 py-3 font-medium">Tanggal</th>
                    <th class="px-4 py-3 font-medium">Tempat</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($competitions as $competition)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-medium">{{ $competition->name }}</td>
                        <td class="px-4 py-3">{{ $competition->start_date->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $competition->city }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('register.create', $competition) }}" class="rounded-md bg-teal-700 px-3 py-1.5 font-medium text-white hover:bg-teal-800">Daftar</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-500">Tidak ada kejuaraan yang membuka pendaftaran saat ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
