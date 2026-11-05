@extends('layouts.public')

@section('title', 'Verifikasi sertifikat')

@section('content')
    @include('public._mast', [
        'kicker' => 'Sertifikat',
        'title' => 'Verifikasi sertifikat',
        'lead' => 'Halaman ini memastikan kode sertifikat yang Anda terima memang diterbitkan panitia.',
    ])

    @if (! $found)
        <p class="note">
            Sertifikat dengan kode <span class="mono">{{ $code }}</span> tidak ditemukan.
        </p>
    @else
        <dl class="sheet">
            <div><dt>Kode</dt><dd class="mono">{{ $code }}</dd></div>
            <div><dt>Jenis</dt><dd>{{ $type === 'winner' ? 'Sertifikat juara' : 'Sertifikat peserta' }}</dd></div>
            <div><dt>Nama</dt><dd>{{ $athleteName }}</dd></div>
            <div><dt>Klub</dt><dd>{{ $clubName }}</dd></div>
            <div><dt>Kejuaraan</dt><dd>{{ $competitionName }}</dd></div>
            <div><dt>Nomor lomba</dt><dd>{{ $eventLabel }}</dd></div>
            @if ($ageGroupName)
                <div><dt>Kelompok umur</dt><dd>{{ $ageGroupName }}</dd></div>
            @endif
            <div><dt>Catatan waktu</dt><dd>{{ $timeLabel }}@if($statusLabel) ({{ $statusLabel }})@endif</dd></div>
            @if ($rank)
                <div><dt>Peringkat</dt><dd>{{ $rank }}</dd></div>
            @endif
        </dl>
    @endif
@endsection
