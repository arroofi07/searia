@extends('layouts.app')

@section('title', 'Import Excel')

@section('content')
    <h1 class="text-2xl font-semibold">Import Excel</h1>
    <p class="mt-1 text-sm text-slate-500">Satu berkas untuk nomor lomba dan peserta.</p>

    <div class="mt-6 max-w-3xl rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-950">
        <p class="font-medium">Buat acara terlebih dahulu</p>
        <p class="mt-1">
            Unggah Excel membutuhkan acara tujuan (wadah kejuaraan).
            @can('create', App\Models\Competition::class)
                Setelah acara dibuat, kembali ke halaman ini untuk unduh template dan unggah berkas.
            @endcan
        </p>
        @can('create', App\Models\Competition::class)
            <a href="{{ route('admin.competitions.create') }}" class="mt-3 inline-flex rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Tambah acara</a>
        @endcan
    </div>

    <div class="mt-6">
        @include('admin.imports._instructions')
    </div>
@endsection
