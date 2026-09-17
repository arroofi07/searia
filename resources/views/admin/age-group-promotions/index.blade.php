@extends('layouts.app')

@section('title', 'Naik kelas')

@section('content')
    <h1 class="text-2xl font-semibold">Naik kelas</h1>
    <p class="mt-1 text-sm text-slate-500">
        {{ $competition->name }} · pindahkan entri ke grup lebih tua. Tahun lahir atlet tidak diubah. Turun kelas ditolak.
    </p>

    @include('admin.registrations._tabs', ['competition' => $competition, 'current' => 'promotions'])

    @error('age_group_id')
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror
    @error('reason')
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    <p class="mt-6 text-sm text-slate-600">
        Klub menandai nomor di Excel dengan bintang, misalnya <span class="font-mono">9*</span>.
        Impor menempatkan anak ke grup lebih tua terdekat yang boleh ikut nomor itu.
        Panitia mengatur atau mengubahnya di halaman ini.
    </p>

    <h2 class="mt-8 text-lg font-medium">Sudah naik kelas</h2>
    <div class="mt-3 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-3 py-2 font-medium">Atlet</th>
                    <th class="px-3 py-2 font-medium">Nomor</th>
                    <th class="px-3 py-2 font-medium">Grup saat ini</th>
                    <th class="px-3 py-2 font-medium">Waktu</th>
                    <th class="px-3 py-2 font-medium">Ubah</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($promoted as $registration)
                    @include('admin.age-group-promotions._row', [
                        'registration' => $registration,
                        'ageGroups' => $ageGroups,
                        'overrides' => $overrides,
                        'competition' => $competition,
                    ])
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada entri yang naik kelas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="mt-10 text-lg font-medium">Bisa dinaikkan</h2>
    <p class="mt-1 text-sm text-slate-500">Entri di grup sesuai tahun lahir, tetapi nomornya punya grup lebih tua.</p>
    <div class="mt-3 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-3 py-2 font-medium">Atlet</th>
                    <th class="px-3 py-2 font-medium">Nomor</th>
                    <th class="px-3 py-2 font-medium">Grup saat ini</th>
                    <th class="px-3 py-2 font-medium">Waktu</th>
                    <th class="px-3 py-2 font-medium">Naikkan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($candidates as $registration)
                    @include('admin.age-group-promotions._row', [
                        'registration' => $registration,
                        'ageGroups' => $ageGroups,
                        'overrides' => $overrides,
                        'competition' => $competition,
                    ])
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Tidak ada entri yang bisa dinaikkan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
