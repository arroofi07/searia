@php
    use App\Enums\CompetitionStatus;

    $fillableTotal = $pairTotal - $emptyCount;
    $progress = $fillableTotal > 0
        ? (int) round(($seededCount / $fillableTotal) * 100)
        : ($pairTotal > 0 ? 100 : 0);
    $allLocked = $seededCount > 0 && $unseeded === 0 && $unlocked === 0;
    $readyToAdvance = $pairTotal > 0 && $unseeded === 0 && $unlocked === 0;
    $canLock = $seededCount > 0 && $unseeded === 0;
    $registrationOpen = in_array($competition->status, [CompetitionStatus::Draft, CompetitionStatus::Registration], true);
    $statusFilter = (string) ($filters['status'] ?? '');
@endphp

@extends('layouts.app')

@section('title', 'Pembagian seri')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Pembagian seri dan lintasan</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $competition->name }}</p>
            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                <span class="rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-700">{{ $competition->pool_lanes }} lintasan</span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-700">{{ $competition->seeding_mode->label() }}</span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-700">{{ $competition->status->label() }}</span>
            </div>
        </div>
        <a href="{{ route('admin.competitions.show', $competition) }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">
            Ke ringkasan acara
        </a>
    </div>

    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'seeding'])

    @error('seeding')
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    @if ($pendingCount > 0)
        <div class="mt-4 flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm leading-6 text-amber-950">
                <p class="font-semibold">Ada {{ $pendingCount }} entri belum disetujui</p>
                <p>Mereka tidak masuk ke seri. Setujui dulu agar pembagian lengkap.</p>
            </div>
            <a href="{{ route('admin.registrations.index', $competition) }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-amber-900 px-4 py-2 text-sm font-medium text-white hover:bg-amber-950">
                Buka antrean pendaftaran
            </a>
        </div>
    @elseif ($registrationOpen)
        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-950">
            Status masih <strong>{{ $competition->status->label() }}</strong>.
            Boleh dicoba, tetapi sebaiknya tunggu pendaftaran ditutup agar susunan tidak berubah karena peserta baru.
        </div>
    @elseif ($pairTotal === 0)
        <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-5 py-4 text-sm text-slate-700">
            Belum ada nomor lomba dengan kelompok umur.
            <a href="{{ route('admin.competitions.events.index', $competition) }}" class="font-medium text-teal-800 hover:underline">Lengkapi nomor lomba</a>
            atau
            <a href="{{ route('admin.competitions.age-groups.index', $competition) }}" class="font-medium text-teal-800 hover:underline">kelompok umur</a>.
        </div>
    @elseif ($unseeded > 0)
        <div class="mt-4 flex flex-col gap-3 rounded-2xl border border-teal-200 bg-teal-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm leading-6 text-teal-950">
                <p class="font-semibold">Langkah berikutnya: bagi seri</p>
                <p>{{ $unseeded }} kombinasi belum punya lintasan. Sistem mengurutkan catatan waktu, lalu mengisi seri.</p>
            </div>
            <form method="POST" action="{{ route('admin.seeding.run', $competition) }}">
                @csrf
                <button class="inline-flex min-h-11 items-center justify-center rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">
                    Bagi seri seluruh kejuaraan
                </button>
            </form>
        </div>
    @elseif ($unlocked > 0)
        <div class="mt-4 flex flex-col gap-3 rounded-2xl border border-sky-200 bg-sky-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm leading-6 text-sky-950">
                <p class="font-semibold">Langkah berikutnya: cek, lalu kunci</p>
                <p>{{ $unlocked }} kombinasi masih pratinjau. Buka susunan jika perlu tukar lintasan, kemudian kunci.</p>
            </div>
            <form method="POST" action="{{ route('admin.seeding.lock', $competition) }}" onsubmit="return confirm('Kunci seluruh seri yang sudah dibagi? Setelah dikunci, mengubah susunan harus mengulang secara paksa.')">
                @csrf
                <button class="inline-flex min-h-11 items-center justify-center rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">
                    Kunci seluruh kejuaraan
                </button>
            </form>
        </div>
    @elseif ($allLocked)
        <div class="mt-4 flex flex-col gap-3 rounded-2xl border border-teal-200 bg-teal-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm leading-6 text-teal-950">
                <p class="font-semibold">Semua seri terkunci</p>
                <p>Kembali ke Ringkasan, lanjutkan status ke <strong>Sudah diseeding</strong> agar buku acara bisa dicetak. Grup tanpa peserta dilewati.</p>
            </div>
            <a href="{{ route('admin.competitions.show', $competition) }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">
                Lanjut di ringkasan
            </a>
        </div>
    @elseif ($readyToAdvance)
        <div class="mt-4 flex flex-col gap-3 rounded-2xl border border-teal-200 bg-teal-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm leading-6 text-teal-950">
                <p class="font-semibold">Tidak ada seri yang perlu dikunci</p>
                <p>Grup tanpa peserta dilewati. Kembali ke Ringkasan, lanjutkan status ke <strong>Sudah diseeding</strong>.</p>
            </div>
            <a href="{{ route('admin.competitions.show', $competition) }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">
                Lanjut di ringkasan
            </a>
        </div>
    @endif

    <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Progres pembagian</p>
                <p class="mt-1 text-sm text-slate-700">
                    {{ $seededCount }} dari {{ $fillableTotal }} kombinasi dengan peserta sudah dibagi · {{ $verifiedCount }} entri disetujui
                    @if ($emptyCount > 0)
                        · {{ $emptyCount }} tanpa peserta dilewati
                    @endif
                </p>
            </div>
            <p class="text-sm font-semibold text-slate-900">{{ $progress }}%</p>
        </div>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}">
            <div class="h-full rounded-full bg-teal-600" style="width: {{ $progress }}%"></div>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('admin.seeding.index', $competition) }}" class="rounded-xl border px-4 py-3 {{ $statusFilter === '' ? 'border-teal-300 bg-teal-50' : 'border-slate-200 hover:bg-slate-50' }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nomor × kelompok umur</p>
                <p class="mt-1 text-xl font-semibold">{{ $pairTotal }}</p>
                <p class="mt-0.5 text-xs text-slate-500">Semua kombinasi</p>
            </a>
            <a href="{{ route('admin.seeding.index', [$competition, 'status' => 'unseeded']) }}" class="rounded-xl border px-4 py-3 {{ $statusFilter === 'unseeded' ? 'border-amber-300 bg-amber-50' : 'border-slate-200 hover:bg-slate-50' }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Belum dibagi</p>
                <p class="mt-1 text-xl font-semibold {{ $unseeded > 0 ? 'text-amber-700' : 'text-teal-800' }}">{{ $unseeded }}</p>
                <p class="mt-0.5 text-xs text-slate-500">Perlu diisi dulu</p>
            </a>
            <a href="{{ route('admin.seeding.index', [$competition, 'status' => 'preview']) }}" class="rounded-xl border px-4 py-3 {{ $statusFilter === 'preview' ? 'border-sky-300 bg-sky-50' : 'border-slate-200 hover:bg-slate-50' }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pratinjau</p>
                <p class="mt-1 text-xl font-semibold">{{ $unlocked }}</p>
                <p class="mt-0.5 text-xs text-slate-500">Boleh dicek dan diubah</p>
            </a>
            <a href="{{ route('admin.seeding.index', [$competition, 'status' => 'locked']) }}" class="rounded-xl border px-4 py-3 {{ $statusFilter === 'locked' ? 'border-teal-300 bg-teal-50' : 'border-slate-200 hover:bg-slate-50' }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Terkunci</p>
                <p class="mt-1 text-xl font-semibold text-teal-800">{{ $lockedCount }}</p>
                <p class="mt-0.5 text-xs text-slate-500">Siap masuk buku acara</p>
            </a>
        </div>
    </section>

    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <form method="POST" action="{{ route('admin.seeding.run', $competition) }}">
            @csrf
            <button class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800 sm:w-auto"
                @if ($seededCount > 0) onclick="return confirm('Mengisi ulang semua kombinasi yang belum dikunci. Yang sudah terkunci dilewati. Lanjutkan?')" @endif>
                Bagi seri seluruh kejuaraan
            </button>
        </form>
        <form method="POST" action="{{ route('admin.seeding.lock', $competition) }}" onsubmit="return confirm('Kunci seluruh seri yang sudah dibagi? Setelah dikunci, mengubah susunan harus mengulang secara paksa.')">
            @csrf
            <button class="inline-flex min-h-11 w-full items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                @disabled(! $canLock)
                title="{{ $canLock ? 'Kunci semua seri' : 'Kunci hanya bisa setelah semua kombinasi dengan peserta dibagi' }}">
                Kunci seluruh kejuaraan
            </button>
        </form>
    </div>
    <p class="mt-2 text-xs text-slate-500">
        “Bagi seri seluruh kejuaraan” mengisi semua kombinasi nomor × kelompok umur yang ada pesertanya.
        Nomor yang sudah dikunci dilewati. Bisa juga membagi satu baris saja di tabel bawah.
    </p>

    <details class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 text-sm leading-6 text-slate-700">
        <summary class="cursor-pointer font-semibold text-slate-900">Apa itu halaman ini? Yang harus panitia kerjakan</summary>
        <div class="mt-3 border-t border-slate-100 pt-3">
            <p>
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
            <ol class="mt-4 list-decimal space-y-2 pl-5">
                <li><strong>Tutup pendaftaran</strong> di Ringkasan acara (status menjadi Pendaftaran ditutup).</li>
                <li><strong>Setujui semua entri</strong> di menu Pendaftaran.</li>
                <li>Tekan <strong>Bagi seri seluruh kejuaraan</strong>.</li>
                <li>Buka <strong>Lihat susunan</strong> per nomor × kelompok umur. Tukar lintasan atau keluarkan peserta jika perlu.</li>
                <li>Jika sudah yakin, tekan <strong>Kunci seluruh kejuaraan</strong>.</li>
                <li>Kembali ke <strong>Ringkasan</strong>, lanjutkan status ke <strong>Sudah diseeding</strong>.</li>
            </ol>
            <p class="mt-3 text-slate-600">{{ $competition->seeding_mode->description() }}</p>
        </div>
    </details>

    <form method="GET" action="{{ route('admin.seeding.index', $competition) }}" class="mt-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="sm:col-span-2 lg:col-span-1">
            <label for="seeding-q" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Cari</label>
            <input id="seeding-q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Nomor atau kelompok umur" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="seeding-event" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Nomor lomba</label>
            <select id="seeding-event" name="event_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua nomor</option>
                @foreach ($filterEvents as $event)
                    <option value="{{ $event->id }}" @selected((string) $filters['event_id'] === (string) $event->id)>
                        {{ $event->event_number }} {{ $event->shortName() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="seeding-group" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Kelompok umur</label>
            <select id="seeding-group" name="age_group_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua grup</option>
                @foreach ($filterAgeGroups as $group)
                    <option value="{{ $group->id }}" @selected((string) $filters['age_group_id'] === (string) $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="seeding-status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
            <select id="seeding-status" name="status" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="" @selected($statusFilter === '')>Semua status</option>
                <option value="unseeded" @selected($statusFilter === 'unseeded')>Belum dibagi</option>
                <option value="empty" @selected($statusFilter === 'empty')>Tidak ada peserta</option>
                <option value="preview" @selected($statusFilter === 'preview')>Pratinjau</option>
                <option value="locked" @selected($statusFilter === 'locked')>Terkunci</option>
            </select>
        </div>
        <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-4">
            <button class="inline-flex min-h-10 items-center rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Saring</button>
            <a href="{{ route('admin.seeding.index', $competition) }}" class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table class="stack-table min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Nomor lomba</th>
                    <th class="px-4 py-3 font-medium">Kelompok umur</th>
                    <th class="px-4 py-3 font-medium">Jumlah seri</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pairs as $pair)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3" data-label="Nomor lomba">
                            <span class="font-medium text-slate-900">{{ $pair['event']->event_number }}</span>
                            {{ $pair['event']->formattedName() }}
                        </td>
                        <td class="px-4 py-3" data-label="Kelompok umur">{{ $pair['ageGroup']->name }}</td>
                        <td class="px-4 py-3" data-label="Jumlah seri">{{ $pair['seeded'] ? $pair['heatCount'] : '—' }}</td>
                        <td class="px-4 py-3" data-label="Status">
                            @include('admin.seeding._status', ['seeded' => $pair['seeded'], 'locked' => $pair['locked'], 'empty' => $pair['empty']])
                        </td>
                        <td class="px-4 py-3 text-right" data-label="Aksi">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @if ($pair['seeded'])
                                    <a href="{{ route('admin.seeding.show', [$competition, $pair['event'], $pair['ageGroup']]) }}" class="inline-flex min-h-9 items-center rounded-md bg-teal-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-teal-800">
                                        Lihat susunan
                                    </a>
                                @endif
                                @unless ($pair['empty'])
                                    <form method="POST" action="{{ route('admin.seeding.run', $competition) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="event_id" value="{{ $pair['event']->id }}">
                                        <input type="hidden" name="age_group_id" value="{{ $pair['ageGroup']->id }}">
                                        @if ($pair['locked'])
                                            <input type="hidden" name="force" value="1">
                                        @endif
                                        <button class="{{ $pair['seeded'] ? 'text-xs text-slate-600 hover:underline' : 'inline-flex min-h-9 items-center rounded-md bg-teal-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-teal-800' }}"
                                            @if ($pair['locked']) onclick="return confirm('Nomor ini sudah dikunci. Ulangi pembagian akan mengganti susunan. Lanjutkan?')" @endif>
                                            {{ $pair['seeded'] ? 'Ulangi pembagian' : 'Bagi seri ini' }}
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                            @if ($pairTotal === 0)
                                Belum ada nomor lomba dengan kelompok umur. Lengkapi pengaturan acara dulu.
                            @else
                                Tidak ada baris yang cocok dengan saringan.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.pagination', ['paginator' => $pairs])
@endsection
