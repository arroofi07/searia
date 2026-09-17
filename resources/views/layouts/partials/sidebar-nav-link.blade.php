@php
    /** @var string $label */
    /** @var string $routeName */
    /** @var bool $active */
    /** @var \App\Models\Competition|null $activeCompetition */
    $href = $activeCompetition ? route($routeName, $activeCompetition) : null;
@endphp

@if ($href)
    <a href="{{ $href }}" class="{{ \App\Support\AdminNavigation::linkClass($active) }}">{{ $label }}</a>
@else
    <span class="{{ \App\Support\AdminNavigation::disabledLinkClass() }}" title="Buat acara terlebih dahulu lewat Dasbor">{{ $label }}</span>
@endif
