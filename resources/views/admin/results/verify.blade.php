@extends('layouts.app')

@section('title', 'Verifikasi hasil')

@section('content')
    <h1 class="text-2xl font-semibold">Verifikasi hasil</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }}</p>
    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'verify'])

    <p class="mt-4 text-sm text-slate-600">{{ $pendingHeats->count() }} seri masih punya hasil belum diverifikasi.</p>

    <div class="mt-6 space-y-4">
        @foreach ($rows as $row)
            @php $heat = $row['heat']; @endphp
            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-medium">
                            Acara {{ $heat->event?->event_number }} · {{ $heat->ageGroup?->name }} · Seri {{ $heat->heat_number }}
                        </h2>
                        <p class="text-sm text-slate-500">
                            {{ $row['result_count'] }} hasil · {{ $row['unverified'] }} belum diverifikasi
                            · {{ $row['locked'] ? 'Terkunci' : 'Belum dikunci' }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($row['unverified'] > 0)
                            <form method="POST" action="{{ route('admin.results.verify-heat', $heat) }}">
                                @csrf
                                <button class="rounded-md bg-teal-700 px-3 py-2 text-sm font-medium text-white hover:bg-teal-800">Verifikasi seri</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.results.verify-event', [$competition, $heat->event]) }}">
                            @csrf
                            <button class="rounded-md border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">Verifikasi seluruh nomor</button>
                        </form>
                    </div>
                </div>

                @if ($row['anomalies']->isNotEmpty())
                    <ul class="mt-3 space-y-1 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-950">
                        @foreach ($row['anomalies'] as $anomaly)
                            <li>{{ $anomaly['message'] }}</li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>
@endsection
