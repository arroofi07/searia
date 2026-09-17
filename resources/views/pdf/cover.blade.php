<div class="cover">
    @if ($logoPath)
        <img class="badge" src="{{ $logoPath }}" alt="Fun Swimming SeaRIA Series 1">
    @endif
    <p class="eyebrow">{{ $coverTitle }}</p>
    <h1>{{ $competitionName ?? '' }}</h1>
    <p class="meta">{{ $venue ?? '' }}@if (! empty($city)), {{ $city }}@endif</p>
    <p class="meta">{{ $dateLabel ?? '' }}</p>
</div>
