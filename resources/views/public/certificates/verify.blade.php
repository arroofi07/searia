@extends('layouts.public')

@section('title', 'Verifikasi sertifikat')

@section('content')
    <div class="mx-auto max-w-xl px-4 py-12">
        <h1 class="text-2xl font-semibold text-slate-900">Verifikasi sertifikat</h1>

        @if (! $found)
            <p class="mt-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                Sertifikat dengan kode <span class="font-mono">{{ $code }}</span> tidak ditemukan.
            </p>
        @else
            <dl class="mt-6 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">Kode</dt>
                    <dd class="font-mono">{{ $code }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Jenis</dt>
                    <dd>{{ $type === 'winner' ? 'Sertifikat juara' : 'Sertifikat peserta' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Nama</dt>
                    <dd>{{ $athleteName }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Klub</dt>
                    <dd>{{ $clubName }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Kejuaraan</dt>
                    <dd>{{ $competitionName }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Nomor lomba</dt>
                    <dd>{{ $eventLabel }}</dd>
                </div>
                @if ($ageGroupName)
                    <div>
                        <dt class="text-slate-500">Kelompok umur</dt>
                        <dd>{{ $ageGroupName }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-slate-500">Catatan waktu</dt>
                    <dd>{{ $timeLabel }}@if($statusLabel) ({{ $statusLabel }})@endif</dd>
                </div>
                @if ($rank)
                    <div>
                        <dt class="text-slate-500">Peringkat</dt>
                        <dd>{{ $rank }}</dd>
                    </div>
                @endif
            </dl>
        @endif
    </div>
@endsection
