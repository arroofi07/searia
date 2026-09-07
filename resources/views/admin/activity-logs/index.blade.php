@extends('layouts.app')

@section('title', 'Jejak audit')

@section('content')
    <h1 class="text-2xl font-semibold">Jejak audit</h1>
    <p class="mt-1 text-sm text-slate-500">Entri tidak dapat diubah atau dihapus.</p>

    <form method="GET" class="mt-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-5">
        <div>
            <label class="block text-xs font-medium text-slate-600">Pengguna</label>
            <select name="user_id" class="mt-1 w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                <option value="">Semua</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600">Tindakan</label>
            <select name="action" class="mt-1 w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                <option value="">Semua</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600">Dari</label>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600">Sampai</label>
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
        </div>
        <div class="flex items-end">
            <button class="rounded-md bg-teal-700 px-3 py-2 text-sm font-medium text-white hover:bg-teal-800">Saring</button>
        </div>
    </form>

    <table class="mt-6 min-w-full text-left text-sm">
        <thead class="text-slate-500">
            <tr>
                <th class="py-2 pr-3">Waktu</th>
                <th class="py-2 pr-3">Pengguna</th>
                <th class="py-2 pr-3">Tindakan</th>
                <th class="py-2 pr-3">Objek</th>
                <th class="py-2">Detail</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <tr class="border-t border-slate-100">
                    <td class="py-2 pr-3 whitespace-nowrap">{{ $log->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                    <td class="py-2 pr-3">{{ $log->user?->name }}</td>
                    <td class="py-2 pr-3 font-mono text-xs">{{ $log->action }}</td>
                    <td class="py-2 pr-3 text-xs">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</td>
                    <td class="py-2"><a href="{{ route('admin.activity-logs.show', $log) }}" class="text-teal-800 hover:underline">Buka</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
