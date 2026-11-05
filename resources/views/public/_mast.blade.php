<header class="mast">
    @if (! empty($back))
        <a class="back" href="{{ $back }}">{{ $backLabel ?? 'Kembali' }}</a>
    @endif
    @if (! empty($kicker))
        <p class="kicker">{{ $kicker }}</p>
    @endif
    <h1>{{ $title }}</h1>
    @if (! empty($meta))
        <p class="meta">{{ $meta }}</p>
    @endif
    @if (! empty($extra))
        <p class="meta">{{ $extra }}</p>
    @endif
    @if (! empty($lead))
        <p class="lead">{{ $lead }}</p>
    @endif
</header>
