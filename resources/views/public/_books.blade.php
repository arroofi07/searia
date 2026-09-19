@php
    $primary = $primary ?? 'start-list';
    $variant = $variant ?? 'buttons';
    $isPills = $variant === 'pills';
    $activeClass = $isPills
        ? 'inline-flex min-h-10 items-center justify-center rounded-full bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800'
        : 'public-btn';
    $idleClass = $isPills
        ? 'inline-flex min-h-10 items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-teal-200 hover:bg-teal-50 hover:text-teal-900'
        : 'public-btn-secondary';
@endphp

<div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
    @if ($competition->hasPublicStartList())
        <a href="{{ route('start-list.show', $competition) }}" class="{{ $primary === 'start-list' ? $activeClass : $idleClass }}">Buku acara</a>
    @endif
    @if ($competition->hasPublicResults())
        <a href="{{ route('results.index', $competition) }}" class="{{ $primary === 'results' ? $activeClass : $idleClass }}">Buku hasil</a>
    @endif
    @if ($competition->hasPublicAwards())
        <a href="{{ route('results.best-club', $competition) }}" class="{{ $primary === 'best-club' ? $activeClass : $idleClass }}">Club terbaik</a>
        <a href="{{ route('results.best-swimmers', $competition) }}" class="{{ $primary === 'best-swimmers' ? $activeClass : $idleClass }}">Atlet terbaik</a>
    @endif
</div>
