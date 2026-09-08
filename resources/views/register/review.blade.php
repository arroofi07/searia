@php
    use App\Support\SwimTime;
@endphp

@extends('layouts.public')

@section('title', 'Tinjau pendaftaran')

@section('content')
    <h1 class="text-2xl font-semibold">{{ $competition->name }}</h1>
    <p class="mt-1 text-sm text-slate-500">Langkah 3 dari 3 · Tinjau dan kirim</p>

    <div class="mt-4 rounded-lg border border-slate-200 bg-white p-4 text-sm">
        <p><strong>{{ $athlete->full_name }}</strong> · {{ $ageGroup?->name }} · {{ $athlete->club->name }}</p>
        <p class="mt-1 text-slate-600">
            Pendaftar: {{ $state['registrant']['name'] }} · {{ $state['registrant']['phone'] }}
            @if ($state['registrant']['email']) · {{ $state['registrant']['email'] }} @endif
        </p>
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-teal-800 text-xs font-semibold uppercase tracking-wide text-white">
                <tr>
                    <th class="w-20 px-4 py-3 text-center">{{ $athlete->gender->eventGender()->value }}</th>
                    <th class="px-4 py-3">Nomor Perlombaan</th>
                    <th class="px-4 py-3">Catatan waktu</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $event)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-2 text-center font-mono font-semibold text-teal-900">{{ $event->paddedEventNumber() }}</td>
                        <td class="px-4 py-2 font-medium tracking-wide">{{ $event->programName() }}</td>
                        <td class="px-4 py-2 font-mono">
                            @if (($parsed[$event->id] ?? null) === false)
                                <span class="text-red-600">Format waktu tidak valid</span>
                            @else
                                {{ $parsed[$event->id]?->format() ?? 'NT' }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <a href="{{ route('register.events', $competition) }}" class="mt-3 inline-block text-sm text-teal-800 hover:underline">Ubah pilihan nomor lomba</a>

    @error('events') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
    @error('submit_token') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror

    <form method="POST" action="{{ route('register.store', $competition) }}" class="mt-6" id="submit-form">
        @csrf
        <input type="hidden" name="submit_token" value="{{ $token }}">
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="terms" value="1" required class="mt-1 h-4 w-4">
            Saya menyetujui <a href="{{ route('terms') }}" class="text-teal-800 hover:underline" target="_blank" rel="noopener">syarat dan ketentuan</a>,
            dan data di atas saya kirim untuk diverifikasi panitia.
        </label>
        @error('terms') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        <button id="submit-btn" class="mt-4 rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Kirim pendaftaran</button>
    </form>

    <script>
        document.getElementById('submit-form').addEventListener('submit', () => {
            document.getElementById('submit-btn').disabled = true;
        });
    </script>
@endsection
