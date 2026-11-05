@extends('layouts.public')

@section('title', 'Cari atlet · SeaRIA')
@section('meta_description', 'Cari atlet dan riwayat hasil kejuaraan yang sudah dipublikasikan.')

@section('content')
    @include('public._mast', [
        'kicker' => '05 — Nama',
        'title' => 'Cari atlet',
        'lead' => 'Cari nama atlet pada kejuaraan yang hasilnya sudah terbit. Yang tampil: nama, klub, dan tahun lahir. Nomor identitas dan tanggal lahir lengkap tidak ditampilkan.',
    ])

    <form method="GET" action="{{ route('public.athletes.search') }}" class="filters">
        <div class="span">
            <label for="q" class="sr-only">Nama atlet</label>
            <input id="q" type="search" name="q" value="{{ $q }}" minlength="2" placeholder="Nama atlet, minimal 2 huruf" class="public-input">
        </div>
        <div>
            <button class="public-btn">Cari</button>
        </div>
    </form>

    @if ($q !== '' && mb_strlen($q) < 2)
        <p class="note">Masukkan minimal dua huruf.</p>
    @endif

    <ul class="list-plain">
        @forelse ($athletes as $athlete)
            <li>
                <a href="{{ route('public.athletes.show', $athlete) }}">
                    <span>
                        <strong>{{ $athlete->full_name }}</strong>
                        <span class="meta">{{ $athlete->club?->name }} · lahir {{ $athlete->birth_year }}</span>
                    </span>
                    <span>Lihat</span>
                </a>
            </li>
        @empty
            @if (mb_strlen($q) >= 2)
                <li>Tidak ada atlet yang cocok.</li>
            @endif
        @endforelse
    </ul>

    @include('partials.pagination', ['paginator' => $athletes])
@endsection
