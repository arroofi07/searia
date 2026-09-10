@php
    $current = $current ?? 1;
    $steps = [
        1 => 'Data diri',
        2 => 'Pilih nomor',
        3 => 'Cek & kirim',
    ];
@endphp

<ol class="mt-5 grid grid-cols-3 gap-2" aria-label="Langkah pendaftaran">
    @foreach ($steps as $number => $label)
        <li class="rounded-2xl px-2 py-2.5 text-center {{ $number === $current ? 'bg-teal-700 text-white shadow-sm' : ($number < $current ? 'bg-teal-50 text-teal-900' : 'bg-slate-100 text-slate-500') }}">
            <span class="block text-[11px] font-medium uppercase tracking-wide {{ $number === $current ? 'text-teal-100' : '' }}">Langkah {{ $number }}</span>
            <span class="mt-0.5 block text-sm font-semibold leading-tight">{{ $label }}</span>
        </li>
    @endforeach
</ol>
