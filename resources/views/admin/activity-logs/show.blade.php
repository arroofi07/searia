@extends('layouts.app')

@section('title', 'Detail audit')

@section('content')
    <p class="text-sm"><a href="{{ route('admin.activity-logs.index') }}" class="text-teal-800 hover:underline">&larr; Kembali</a></p>
    <h1 class="mt-2 text-2xl font-semibold">{{ $log->action }}</h1>
    <p class="mt-1 text-sm text-slate-500">
        {{ $log->user?->name }} · {{ $log->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}
        @if ($log->ip_address) · IP {{ $log->ip_address }} @endif
    </p>
    @if ($log->reason)
        <p class="mt-3 text-sm"><span class="font-medium">Alasan:</span> {{ $log->reason }}</p>
    @endif

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <section class="rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-medium text-slate-600">Sebelum</h2>
            <pre class="mt-2 overflow-auto text-xs">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </section>
        <section class="rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-medium text-slate-600">Sesudah</h2>
            <pre class="mt-2 overflow-auto text-xs">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </section>
    </div>
@endsection
