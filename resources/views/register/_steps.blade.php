@php
    $current = $current ?? 1;
    $steps = [
        1 => 'Data peserta',
        2 => 'Kode acara',
        3 => 'Cek & kirim',
    ];
@endphp

<ol class="steps" aria-label="Langkah pendaftaran">
    @foreach ($steps as $number => $label)
        <li class="{{ $number === $current ? 'is-now' : ($number < $current ? 'is-done' : '') }}">
            <span>0{{ $number }}</span>
            <strong>{{ $label }}</strong>
        </li>
    @endforeach
</ol>
