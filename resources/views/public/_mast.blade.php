@if (! empty($competition))
    <header class="mast-row mast-with-logo">
        <div class="mast-id">
            @include('layouts.partials.event-logo', [
                'competition' => $competition,
                'class' => 'meet-logo',
                'alt' => $competition->name,
            ])
            <div class="mast mast-inset">
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
            </div>
        </div>
    </header>
@else
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
@endif
