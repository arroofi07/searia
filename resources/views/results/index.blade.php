@extends('layouts.public')

@section('title', 'Hasil · '.$competition->name)
@section('meta_description', 'Hasil resmi '.$competition->name.($competition->published_at ? ' · dipublikasikan '.$competition->published_at->format('d/m/Y') : ''))
@section('og_title', 'Hasil · '.$competition->name)

@section('content')
    <div class="mast-row">
        <div class="mast-id">
            @include('layouts.partials.event-logo', ['class' => 'meet-logo'])
            @include('public._mast', [
                'kicker' => 'Buku hasil',
                'title' => 'Hasil lomba',
                'meta' => $competition->name,
                'extra' => $competition->published_at
                    ? 'Dipublikasikan '.$competition->published_at->timezone(config('app.timezone'))->format('d/m/Y H:i')
                    : null,
                'lead' => 'Waktu di sini adalah hasil hari lomba. PB berarti lebih cepat dari catatan waktu saat daftar.',
            ])
        </div>
        <div class="toolbar">
            @include('public._books', ['competition' => $competition, 'primary' => 'results'])
            <a href="{{ route('results.pdf', $competition) }}" class="public-btn-secondary">Unduh PDF buku hasil</a>
            @if ($competition->hasPublicAwards())
                <a href="{{ route('results.medals', $competition) }}" class="public-btn-secondary">Rekap medali</a>
                <a href="{{ route('results.standings', $competition) }}" class="public-btn-secondary">Klasemen klub</a>
            @endif
        </div>
    </div>

    @if ($competition->hasPublicResults() && $competition->status !== \App\Enums\CompetitionStatus::Published)
        <div class="note">Hasil sementara — lomba belum dipublikasikan. Nomor yang belum selesai belum masuk atau belum ada waktu.</div>
    @elseif ($preview)
        <div class="note">Pratinjau panitia — belum dipublikasikan.</div>
    @endif

    @forelse ($tables as $table)
        <section class="program">
            <div class="border-b border-slate-100 px-4 py-3">
                <h2 class="font-medium">{{ $table->eventTitle }}</h2>
            </div>
            @include('results._table', ['table' => $table, 'formatTime' => $formatTime, 'competition' => $competition])
        </section>
        @empty
            <p class="mt-8 text-sm text-slate-500">Belum ada hasil.</p>
        @endforelse

    @include('partials.pagination', ['paginator' => $tables])
@endsection
