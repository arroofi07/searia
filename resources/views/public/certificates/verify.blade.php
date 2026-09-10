@extends('layouts.public')

@section('title', 'Verifikasi sertifikat')

@section('content')
    <div class="mx-auto max-w-xl">
        <h1 class="text-2xl font-semibold text-slate-900">Verifikasi sertifikat</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Halaman ini memastikan kode sertifikat yang Anda terima memang diterbitkan panitia.</p>

        @if (! $found)
            <p class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                Sertifikat dengan kode <span class="font-mono">{{ $code }}</span> tidak ditemukan.
            </p>
        @else
            <dl class="mt-6 space-y-3 rounded-2xl border border-slate-200 bg-white p-5 text-sm">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kode</dt>
                    <dd class="mt-0.5 font-mono">{{ $code }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis</dt>
                    <dd class="mt-0.5">{{ $type === 'winner' ? 'Sertifikat juara' : 'Sertifikat peserta' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</dt>
                    <dd class="mt-0.5">{{ $athleteName }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Klub</dt>
                    <dd class="mt-0.5">{{ $clubName }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kejuaraan</dt>
                    <dd class="mt-0.5">{{ $competitionName }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nomor lomba</dt>
                    <dd class="mt-0.5">{{ $eventLabel }}</dd>
                </div>
                @if ($ageGroupName)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kelompok umur</dt>
                        <dd class="mt-0.5">{{ $ageGroupName }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Catatan waktu</dt>
                    <dd class="mt-0.5">{{ $timeLabel }}@if($statusLabel) ({{ $statusLabel }})@endif</dd>
                </div>
                @if ($rank)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Peringkat</dt>
                        <dd class="mt-0.5">{{ $rank }}</dd>
                    </div>
                @endif
            </dl>
        @endif
    </div>
@endsection
