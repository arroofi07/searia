@php
    use App\Support\SwimTime;
@endphp

@extends('layouts.app')

@section('title', 'Ringkasan pendaftaran')

@section('content')
    <h1 class="text-2xl font-semibold">{{ $competition->name }}</h1>
    <p class="mt-1 text-sm text-slate-500">Langkah 3 dari 3 · Tinjau dan kirim</p>

    <div class="mt-4 rounded-lg border border-slate-200 bg-white p-4 text-sm">
        <p>{{ $athlete->full_name }} · {{ $ageGroup?->name }} · {{ $athlete->club->name }}</p>
    </div>

    <table class="mt-4 min-w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="px-4 py-2 font-medium">Nomor</th>
                <th class="px-4 py-2 font-medium">Catatan waktu</th>
                <th class="px-4 py-2 font-medium">Biaya</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            @foreach ($events as $event)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-2">{{ $event->event_number }} {{ $event->formattedName() }}</td>
                    <td class="px-4 py-2">
                        @if ($parsed[$event->id] === false)
                            <span class="text-red-700">Format tidak valid</span>
                        @else
                            {{ $parsed[$event->id]?->format() ?? 'NT' }}
                        @endif
                    </td>
                    <td class="px-4 py-2">Rp {{ number_format($competition->fee_per_event, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="border-t border-slate-200 font-medium">
                <td class="px-4 py-2" colspan="2">Total</td>
                <td class="px-4 py-2">Rp {{ number_format($totalFee, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    @error('events') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
    @error('submit_token') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror

    <form method="POST" action="{{ route('registrations.store', $competition) }}" class="mt-6" id="submit-form">
        @csrf
        <input type="hidden" name="submit_token" value="{{ $token }}">
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" required class="mt-1 h-4 w-4">
            Saya menyetujui data di atas dan mengirim pendaftaran untuk diverifikasi panitia.
        </label>
        <button id="submit-btn" class="mt-4 rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Kirim pendaftaran</button>
    </form>

    <script>
        document.getElementById('submit-form').addEventListener('submit', () => {
            document.getElementById('submit-btn').disabled = true;
        });
    </script>
@endsection
