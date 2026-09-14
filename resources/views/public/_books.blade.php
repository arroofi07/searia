@php
    $primary = $primary ?? 'start-list';
@endphp

<div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
    @if ($competition->hasPublicResults() && $primary === 'results')
        <a href="{{ route('results.index', $competition) }}" class="public-btn">Buku hasil</a>
        <a href="{{ route('results.pdf', [$competition, 'inline' => 1]) }}" target="_blank" rel="noopener" class="public-btn-secondary">PDF hasil</a>
        <a href="{{ route('start-list.show', $competition) }}" class="public-btn-secondary">Buku acara</a>
        <a href="{{ route('start-list.pdf', [$competition, 'inline' => 1]) }}" target="_blank" rel="noopener" class="public-btn-secondary">PDF acara</a>
    @elseif ($competition->hasPublicStartList())
        <a href="{{ route('start-list.show', $competition) }}" class="public-btn">Buku acara</a>
        <a href="{{ route('start-list.pdf', [$competition, 'inline' => 1]) }}" target="_blank" rel="noopener" class="public-btn-secondary">PDF acara</a>
        @if ($competition->hasPublicResults())
            <a href="{{ route('results.index', $competition) }}" class="public-btn-secondary">Buku hasil</a>
            <a href="{{ route('results.pdf', [$competition, 'inline' => 1]) }}" target="_blank" rel="noopener" class="public-btn-secondary">PDF hasil</a>
        @endif
    @endif
</div>
