@php
    use App\Enums\CompetitionStatus;
@endphp

@extends('layouts.app')

@section('title', 'Pembagian seri')

@section('content')
    <h1 class="text-2xl font-semibold">Pembagian seri dan lintasan</h1>
    <p class="mt-1 text-sm text-slate-600">
        {{ $competition->name }} · {{ $competition->pool_lanes }} lintasan · cara bagi: {{ $competition->seeding_mode->label() }}
        · status sekarang: {{ $competition->status->label() }}
    </p>

    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'seeding'])

    @error('seeding')
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    <section class="mt-6 rounded-xl border border-teal-200 bg-teal-50/70 p-5 text-sm leading-6 text-teal-950">
        <h2 class="text-base font-semibold">Apa itu halaman ini?</h2>
        <p class="mt-1">
            Di dunia renang ini disebut <strong>seeding</strong>. Artinya: sistem menyusun
            <strong>siapa berenang di seri berapa</strong> dan <strong>lintasan berapa</strong>,
            berdasarkan <strong>catatan waktu saat daftar</strong> — bukan hasil lomba nanti.
        </p>
        <ul class="mt-3 list-disc space-y-1 pl-5">
            <li>Kolam hanya punya {{ $competition->pool_lanes }} lintasan. Jika peserta lebih banyak, mereka dibagi ke beberapa <strong>seri</strong> (gelombang).</li>
            <li>Yang lebih cepat biasanya di <strong>lintasan tengah</strong> dan di <strong>seri terakhir</strong>.</li>
            <li>Tanpa catatan waktu (<strong>NT</strong>) diletakkan di seri belakang.</li>
            <li>Hanya pendaftaran yang sudah <strong>disetujui</strong> yang masuk. Yang masih menunggu verifikasi tidak ikut.</li>
        </ul>
    </section>

    <section class="mt-4 rounded-xl border border-slate-200 bg-white p-5 text-sm leading-6 text-slate-700">
        <h2 class="font-semibold text-slate-900">Yang harus panitia kerjakan</h2>
        <ol class="mt-2 list-decimal space-y-2 pl-5">
            <li>
                <strong>Tutup pendaftaran</strong> di Ringkasan acara (status menjadi Pendaftaran ditutup),
                supaya tidak ada peserta baru masuk di tengah jalan.
            </li>
            <li>
                <strong>Setujui semua entri</strong> di menu Pendaftaran. Yang belum disetujui tidak masuk ke seri.
            </li>
            <li>
                Tekan <strong>Bagi seri seluruh kejuaraan</strong>. Sistem mengurutkan catatan waktu, lalu mengisi seri dan lintasan.
            </li>
            <li>
                Buka <strong>Lihat susunan</strong> per nomor × kelompok umur. Tukar lintasan atau keluarkan peserta jika perlu.
            </li>
            <li>
                Jika sudah yakin, tekan <strong>Kunci seluruh kejuaraan</strong>. Setelah terkunci, ulangi hanya dengan paksa (susunan lama diganti).
            </li>
            <li>
                Kembali ke <strong>Ringkasan</strong>, lanjutkan status ke <strong>Sudah diseeding</strong>.
                Baru setelah itu buku acara bisa dicetak dan dilihat publik.
            </li>
        </ol>
        <p class="mt-3 text-slate-600">{{ $competition->seeding_mode->description() }}</p>
    </section>

    @if ($competition->status === CompetitionStatus::Draft || $competition->status === CompetitionStatus::Registration)
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-950">
            Status masih <strong>{{ $competition->status->label() }}</strong>.
            Boleh dicoba, tetapi sebaiknya tunggu pendaftaran ditutup agar susunan tidak berubah karena peserta baru.
        </div>
    @endif

    @if ($pendingCount > 0)
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-950">
            Ada <strong>{{ $pendingCount }}</strong> entri masih menunggu verifikasi.
            Mereka <strong>tidak masuk</strong> ke seri.
            <a href="{{ route('admin.registrations.index', $competition) }}" class="font-medium text-amber-900 underline">Buka antrean pendaftaran</a>.
        </div>
    @endif

    <dl class="mt-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-5 text-sm sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nomor × kelompok umur</dt>
            <dd class="mt-1 text-lg font-semibold">{{ count($pairs) }}</dd>
            <p class="mt-0.5 text-xs text-slate-500">Pembagian dihitung terpisah per kombinasi ini, bukan per nomor saja.</p>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sudah disetujui</dt>
            <dd class="mt-1 text-lg font-semibold text-teal-800">{{ $verifiedCount }}</dd>
            <p class="mt-0.5 text-xs text-slate-500">Entri yang akan masuk ke seri.</p>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Belum dibagi</dt>
            <dd class="mt-1 text-lg font-semibold {{ $unseeded > 0 ? 'text-amber-700' : 'text-teal-800' }}">{{ $unseeded }}</dd>
            <p class="mt-0.5 text-xs text-slate-500">Kombinasi yang belum punya seri.</p>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sudah dibagi, belum dikunci</dt>
            <dd class="mt-1 text-lg font-semibold">{{ $unlocked }}</dd>
            <p class="mt-0.5 text-xs text-slate-500">Boleh dicek dan diubah, lalu dikunci.</p>
        </div>
    </dl>

    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <form method="POST" action="{{ route('admin.seeding.run', $competition) }}">
            @csrf
            <button class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800 sm:w-auto">
                Bagi seri seluruh kejuaraan
            </button>
        </form>
        <form method="POST" action="{{ route('admin.seeding.lock', $competition) }}" onsubmit="return confirm('Kunci seluruh seri yang sudah dibagi? Setelah dikunci, mengubah susunan harus mengulang secara paksa.')">
            @csrf
            <button class="inline-flex min-h-11 w-full items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50 sm:w-auto">
                Kunci seluruh kejuaraan
            </button>
        </form>
        <a href="{{ route('admin.competitions.show', $competition) }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">
            Ke ringkasan acara
        </a>
    </div>
    <p class="mt-2 text-xs text-slate-500">
        “Bagi seri seluruh kejuaraan” mengisi semua kombinasi nomor × kelompok umur yang ada pesertanya.
        Bisa juga membagi satu baris saja di tabel bawah.
    </p>

    <div class="mt-8 overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Nomor lomba</th>
                    <th class="px-4 py-3 font-medium">Kelompok umur</th>
                    <th class="px-4 py-3 font-medium">Jumlah seri</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pairs as $pair)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">{{ $pair['event']->event_number }} {{ $pair['event']->formattedName() }}</td>
                        <td class="px-4 py-3">{{ $pair['ageGroup']->name }}</td>
                        <td class="px-4 py-3">{{ $pair['heatCount'] }}</td>
                        <td class="px-4 py-3">
                            @if (! $pair['seeded'])
                                <span class="text-amber-700">Belum dibagi</span>
                            @elseif ($pair['locked'])
                                <span class="text-teal-800">Terkunci</span>
                            @else
                                <span class="text-slate-700">Pratinjau — belum dikunci</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
                                @if ($pair['seeded'])
                                    <a href="{{ route('admin.seeding.show', [$competition, $pair['event'], $pair['ageGroup']]) }}" class="text-teal-800 hover:underline">Lihat susunan</a>
                                @endif
                                <form method="POST" action="{{ route('admin.seeding.run', $competition) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="event_id" value="{{ $pair['event']->id }}">
                                    <input type="hidden" name="age_group_id" value="{{ $pair['ageGroup']->id }}">
                                    @if ($pair['locked'])
                                        <input type="hidden" name="force" value="1">
                                    @endif
                                    <button class="text-teal-800 hover:underline">{{ $pair['seeded'] ? 'Ulangi pembagian' : 'Bagi seri ini' }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada nomor lomba dengan kelompok umur. Lengkapi pengaturan acara dulu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
