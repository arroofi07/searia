@php
    $primary = $primary ?? 'start-list';
@endphp

<div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
    @if ($competition->hasPublicStartList())
        <a href="{{ route('start-list.show', $competition) }}" class="{{ $primary === 'start-list' ? 'public-btn' : 'public-btn-secondary' }}">Buku acara</a>
    @endif
    @if ($competition->hasPublicResults())
        <a href="{{ route('results.index', $competition) }}" class="{{ $primary === 'results' ? 'public-btn' : 'public-btn-secondary' }}">Buku hasil</a>
        <a href="{{ route('results.best-club', $competition) }}" class="{{ $primary === 'best-club' ? 'public-btn' : 'public-btn-secondary' }}">Club terbaik</a>
        <a href="{{ route('results.best-swimmers', $competition) }}" class="{{ $primary === 'best-swimmers' ? 'public-btn' : 'public-btn-secondary' }}">Atlet terbaik</a>
    @endif
</div>
