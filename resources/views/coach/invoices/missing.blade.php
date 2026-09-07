@extends('layouts.app')

@section('title', 'Tagihan klub')

@section('content')
    <h1 class="text-2xl font-semibold">Tagihan klub</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }}</p>
    <p class="mt-6 rounded-md border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
        Belum ada tagihan untuk klub Anda. Panitia akan menerbitkan tagihan setelah entri diverifikasi.
    </p>
    <a href="{{ route('coach.registrations.index', $competition) }}" class="mt-4 inline-block text-sm text-teal-800 hover:underline">Kembali ke ringkasan entri</a>
@endsection
