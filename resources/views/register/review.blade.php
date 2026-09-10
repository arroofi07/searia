@extends('layouts.public')

@section('title', 'Tinjau pendaftaran')
@section('meta_description', 'Periksa data sebelum mengirim pendaftaran '.$competition->name)

@section('content')
    <div class="mx-auto max-w-2xl">
        <p class="text-sm font-semibold uppercase tracking-wide text-teal-800">Pendaftaran</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $competition->name }}</h1>
        <p class="mt-1 text-sm leading-6 text-slate-600">Periksa sekali lagi. Setelah dikirim, Anda tidak bisa mengubah sendiri — koreksi lewat panitia.</p>

        @include('register._steps', ['current' => 3])

        <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow-sm">
            <h2 class="font-semibold text-slate-900">{{ $athlete->full_name }}</h2>
            <p class="mt-1 text-slate-600">{{ $ageGroup?->name }} · {{ $athlete->club->name }}</p>
            <p class="mt-2 text-slate-600">
                Pendaftar: {{ $state['registrant']['name'] }} · {{ $state['registrant']['phone'] }}
                @if ($state['registrant']['email']) · {{ $state['registrant']['email'] }} @endif
            </p>
        </div>

        <ul class="mt-4 space-y-3">
            @foreach ($events as $event)
                <li class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-mono text-sm font-bold text-teal-800">{{ $event->paddedEventNumber() }}</p>
                            <p class="mt-0.5 font-medium text-slate-900">{{ $event->programName() }}</p>
                        </div>
                        <p class="shrink-0 text-right font-mono text-sm font-semibold">
                            @if (($parsed[$event->id] ?? null) === false)
                                <span class="text-red-600">Format waktu tidak valid</span>
                            @else
                                {{ $parsed[$event->id]?->format() ?? 'NT' }}
                            @endif
                        </p>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        @if (($parsed[$event->id] ?? null) === false)
                            Kembali ke langkah sebelumnya dan perbaiki catatan waktu.
                        @elseif ($parsed[$event->id] === null)
                            Tidak ada catatan waktu — atlet diletakkan di seri belakang.
                        @else
                            Dipakai untuk pembagian seri dan lintasan, bukan hasil lomba.
                        @endif
                    </p>
                </li>
            @endforeach
        </ul>

        <a href="{{ route('register.events', $competition) }}" class="mt-4 inline-flex min-h-11 items-center text-sm font-medium text-teal-800 hover:underline">Ubah pilihan nomor lomba</a>

        @error('events') <p class="mt-3 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p> @enderror
        @error('submit_token') <p class="mt-3 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p> @enderror

        <form method="POST" action="{{ route('register.store', $competition) }}" class="mt-6" id="submit-form">
            @csrf
            <input type="hidden" name="submit_token" value="{{ $token }}">
            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-sm leading-6">
                <input type="checkbox" name="terms" value="1" required class="mt-1 h-5 w-5 shrink-0 rounded border-slate-300 text-teal-700">
                <span>
                    Saya menyetujui
                    <a href="{{ route('terms') }}" class="font-medium text-teal-800 hover:underline" target="_blank" rel="noopener">syarat dan ketentuan</a>,
                    dan data di atas saya kirim untuk diperiksa panitia.
                </span>
            </label>
            @error('terms') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            <button id="submit-btn" class="public-btn mt-4">Kirim pendaftaran</button>
        </form>
    </div>

    <script>
        document.getElementById('submit-form').addEventListener('submit', () => {
            document.getElementById('submit-btn').disabled = true;
        });
    </script>
@endsection
