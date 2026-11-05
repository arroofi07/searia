@extends('layouts.public')

@section('title', $athlete->full_name.' · SeaRIA')
@section('meta_description', 'Riwayat hasil '.$athlete->full_name.' di kejuaraan yang sudah dipublikasikan.')

@section('content')
    @include('public._mast', [
        'back' => route('public.athletes.search'),
        'backLabel' => 'Pencarian',
        'kicker' => 'Profil',
        'title' => $athlete->full_name,
        'meta' => trim(($athlete->club?->name ? $athlete->club->name.' · ' : '').'Tahun lahir '.$athlete->birth_year),
    ])

    @if ($bestByEvent !== [])
        <section>
            <h2>Catatan terbaik per nomor</h2>
            <p class="lead">Waktu tercepat yang sudah dipublikasikan untuk nomor yang sama.</p>
            <ul class="list-plain">
                @foreach ($bestByEvent as $best)
                    <li>
                        <strong>{{ $best['label'] }}</strong>
                        <span class="pb">{{ ($formatTime)($best['time_ms']) }}</span>
                        <span class="meta">{{ $best['competition'] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="gap-top">
        <h2>Riwayat lomba</h2>
        <div class="program">
            <table class="stack-table">
                <thead>
                    <tr>
                        <th>Kejuaraan</th>
                        <th>Acara</th>
                        <th>Hasil</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($results as $result)
                        @php $event = $result->heatLane?->heat?->event; @endphp
                        <tr>
                            <td data-label="Kejuaraan">
                                @if ($event?->competition)
                                    <a href="{{ route('results.index', $event->competition) }}">{{ $event->competition->name }}</a>
                                @endif
                            </td>
                            <td data-label="Acara">{{ $event?->event_number }} {{ $event?->formattedName() }}</td>
                            <td data-label="Hasil"><span class="mono">{{ ($formatTime)($result->time_ms) }}</span></td>
                            <td data-label="Status">{{ $result->status->label() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" data-label="Catatan">Belum ada hasil publik.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.pagination', ['paginator' => $results])
    </section>
@endsection
