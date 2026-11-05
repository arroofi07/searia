@extends('layouts.public')

@section('title', 'Tinjau pendaftaran')
@section('meta_description', 'Periksa data sebelum mengirim pendaftaran '.$competition->name)

@section('content')
    @include('public._mast', [
        'kicker' => 'Pendaftaran · langkah 3',
        'title' => $competition->name,
        'lead' => 'Periksa sekali lagi. Setelah dikirim, Anda tidak bisa mengubah sendiri — koreksi lewat panitia.',
    ])

    @include('register._steps', ['current' => 3])

    <dl class="facts">
        <div>
            <dt>Nama lengkap</dt>
            <dd>{{ $athlete->full_name }}</dd>
        </div>
        <div>
            <dt>L/P · Tahun lahir</dt>
            <dd>{{ $athlete->gender->value }} · {{ $athlete->birth_year }} ({{ $ageGroup?->name }})</dd>
        </div>
        <div>
            <dt>Klub/sekolah</dt>
            <dd>{{ $athlete->club->name }}</dd>
        </div>
        <div>
            <dt>Kabupaten/kota</dt>
            <dd>{{ $athlete->club->city ?: '—' }}</dd>
        </div>
    </dl>

    <ul class="list-plain">
        @foreach ($events as $event)
            <li>
                <p class="kicker">Kode acara {{ $event->paddedEventNumber() }}</p>
                <strong>{{ $event->programName() }}</strong>
                <p class="meta">
                    Catatan waktu:
                    @if (($parsed[$event->id] ?? null) === false)
                        format tidak valid
                    @else
                        {{ $parsed[$event->id]?->format() ?? 'NT' }}
                    @endif
                </p>
                <p class="meta">
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

    <a class="back" href="{{ route('register.events', $competition) }}">Ubah kode acara</a>

    @error('events') <p class="note">{{ $message }}</p> @enderror
    @error('submit_token') <p class="note">{{ $message }}</p> @enderror

    <form method="POST" action="{{ route('register.store', $competition) }}" class="panel" id="submit-form">
        @csrf
        <input type="hidden" name="submit_token" value="{{ $token }}">
        <label class="pick">
            <input type="checkbox" name="terms" value="1" required class="event-box">
            <span>
                Saya menyetujui
                <a href="{{ route('terms') }}" target="_blank" rel="noopener">syarat dan ketentuan</a>,
                dan data di atas saya kirim untuk diperiksa panitia.
            </span>
        </label>
        @error('terms') <p class="note">{{ $message }}</p> @enderror
        <div class="gap-top">
            <button id="submit-btn" class="public-btn">Kirim pendaftaran</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.getElementById('submit-form').addEventListener('submit', () => {
        document.getElementById('submit-btn').disabled = true;
    });
</script>
@endpush
