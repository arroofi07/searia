@extends('layouts.public')

@section('title', 'Jadwal · '.$competition->name)
@section('meta_description', 'Jadwal pendaftaran, technical meeting, dan susunan acara '.$competition->name)

@section('content')
    @include('public._mast', [
        'back' => route('home'),
        'backLabel' => 'Beranda',
        'kicker' => 'Jadwal',
        'title' => $competition->name,
        'meta' => $competition->venue.', '.$competition->city,
        'lead' => 'Tanggal penting kejuaraan dan daftar nomor lomba. Nomor PA untuk putra, PI untuk putri. Yang tampil saat daftar sudah disaring sesuai jenis kelamin dan tahun lahir atlet.',
    ])

    <ol class="miles">
        @foreach ($milestones as $milestone)
            <li class="{{ $milestone['past'] ? 'is-past' : '' }}">
                <h2>{{ $milestone['label'] }}</h2>
                <p class="meta">
                    @if ($milestone['start'] === null)
                        Belum dijadwalkan
                    @elseif ($milestone['end'] && $milestone['start']->equalTo($milestone['end']))
                        {{ $milestone['start']->timezone(config('app.timezone'))->translatedFormat('d F Y H:i') }}
                    @elseif ($milestone['label'] === 'Hari lomba')
                        {{ $milestone['start']->translatedFormat('d F Y') }}
                        @if (! $competition->start_date->equalTo($competition->end_date))
                            – {{ $competition->end_date->translatedFormat('d F Y') }}
                        @endif
                    @else
                        {{ $milestone['start']->timezone(config('app.timezone'))->translatedFormat('d F Y H:i') }}
                        – {{ $milestone['end']->timezone(config('app.timezone'))->translatedFormat('d F Y H:i') }}
                    @endif
                </p>
            </li>
        @endforeach
    </ol>

    <section class="program">
        <div class="program-banner">
            <p class="kicker">Susunan acara perlombaan</p>
            <p>{{ $competition->name }}</p>
        </div>

        @forelse ($programBySession as $session => $rows)
            <h2>H{{ $session }}</h2>
            <div class="scroll">
                <table>
                    <thead>
                        <tr>
                            <th>PA<br>Putra</th>
                            <th>Nomor perlombaan</th>
                            <th>PI<br>Putri</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td class="mono">{{ $row['pa']?->paddedEventNumber() ?? '—' }}</td>
                                <td>{{ $row['label'] }}</td>
                                <td class="mono">{{ $row['pi']?->paddedEventNumber() ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <p class="note">Nomor lomba belum dikonfigurasi.</p>
        @endforelse
    </section>

    @include('partials.pagination', ['paginator' => $programPages])

    <div class="toolbar gap-top">
        @include('public._books', ['competition' => $competition, 'primary' => $competition->hasPublicResults() ? 'results' : 'start-list'])
        @if ($competition->status === \App\Enums\CompetitionStatus::Registration)
            <a href="{{ route('register.create', $competition) }}" class="public-btn">Daftar sekarang</a>
        @endif
    </div>
@endsection
