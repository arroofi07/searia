@php
    use App\Enums\CompetitionStatus;
@endphp

@extends('layouts.app')

@section('title', 'Kesiapan kejuaraan')

@section('content')
    <h1 class="text-2xl font-semibold">{{ $competition->name }}</h1>
    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'readiness'])

    <p class="mt-4 text-sm text-slate-500">Periksa konfigurasi sebelum pendaftaran dibuka. Temuan di bawah menggagalkan pembukaan.</p>

    @if ($ready)
        <div class="mt-6 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">Kejuaraan siap dibuka pendaftarannya.</div>
    @else
        <ul class="mt-6 space-y-2 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            @foreach ($findings as $finding)
                <li>{{ $finding['message'] }}</li>
            @endforeach
        </ul>
    @endif

    @if ($competition->status === CompetitionStatus::Draft)
        <form method="POST" action="{{ route('admin.competitions.status', $competition) }}" class="mt-6">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="{{ CompetitionStatus::Registration->value }}">
            <button
                @disabled(! $canOpenRegistration)
                class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800 disabled:cursor-not-allowed disabled:bg-slate-300">
                Buka pendaftaran
            </button>
        </form>
    @endif
@endsection
