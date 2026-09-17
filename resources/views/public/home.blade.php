@extends('layouts.public')

@section('title', 'Beranda · SeaRIA')
@section('meta_description', 'Kejuaraan renang aktif, pendaftaran, dan hasil terbaru di SeaRIA.')

@section('content')
    <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-teal-800 via-teal-900 to-slate-900 px-5 py-10 text-white sm:px-10 sm:py-14">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-teal-200">Aquatic SeaRIA</p>
                <h1 class="mt-2 max-w-2xl text-3xl font-semibold tracking-tight sm:text-4xl">Daftar lomba, lihat buku acara, dan cek hasil</h1>
                <p class="mt-3 max-w-xl text-sm leading-6 text-teal-50/90 sm:text-base">
                    Untuk peserta dan orang tua. Tidak perlu membuat akun. Panitia yang memverifikasi data setelah Anda kirim.
                </p>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <a href="{{ route('register.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-white px-5 py-3 text-base font-semibold text-teal-900 hover:bg-teal-50">Daftar lomba</a>
                    @if ($featuredStartList)
                        <a href="{{ route('start-list.show', $featuredStartList) }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-white/40 px-5 py-3 text-base font-semibold text-white hover:bg-white/10">Buku acara</a>
                    @endif
                    @if ($featuredResults)
                        <a href="{{ route('results.index', $featuredResults) }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-white/40 px-5 py-3 text-base font-semibold text-white hover:bg-white/10">Buku hasil</a>
                        <a href="{{ route('results.best-club', $featuredResults) }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-white/40 px-5 py-3 text-base font-semibold text-white hover:bg-white/10">Club terbaik</a>
                        <a href="{{ route('results.best-swimmers', $featuredResults) }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-white/40 px-5 py-3 text-base font-semibold text-white hover:bg-white/10">Atlet terbaik</a>
                    @endif
                    <a href="{{ route('public.athletes.search') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-white/40 px-5 py-3 text-base font-semibold text-white hover:bg-white/10">Cari hasil atlet</a>
                    <a href="{{ route('archive.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-white/40 px-5 py-3 text-base font-semibold text-white hover:bg-white/10">Arsip kejuaraan</a>
                </div>
            </div>
            <div class="mx-auto shrink-0 rounded-3xl bg-white p-5 shadow-sm lg:mx-0">
                @include('layouts.partials.brand-mark', ['class' => 'h-36 w-auto sm:h-44', 'alt' => 'Aquatic SeaRIA'])
            </div>
        </div>
    </section>

    <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-5">
        <h2 class="text-base font-semibold text-slate-900">Cara daftar, singkatnya</h2>
        <ol class="mt-3 grid gap-3 sm:grid-cols-3">
            <li class="rounded-xl bg-slate-50 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Langkah 1</p>
                <p class="mt-1 text-sm font-medium">Isi data peserta</p>
                <p class="mt-1 text-sm leading-5 text-slate-600">Nama lengkap, L/P, tahun lahir, klub/sekolah, dan kabupaten/kota.</p>
            </li>
            <li class="rounded-xl bg-slate-50 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Langkah 2</p>
                <p class="mt-1 text-sm font-medium">Pilih kode acara + catatan waktu</p>
                <p class="mt-1 text-sm leading-5 text-slate-600">Catatan waktu (seed) untuk pembagian lintasan. Kosong = NT.</p>
            </li>
            <li class="rounded-xl bg-slate-50 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Langkah 3</p>
                <p class="mt-1 text-sm font-medium">Simpan kode REG-…</p>
                <p class="mt-1 text-sm leading-5 text-slate-600">Kode itu dipakai jika panitia perlu menghubungi atau memperbaiki data.</p>
            </li>
        </ol>
    </section>

    <section class="mt-10">
        <h2 class="text-xl font-semibold">Pendaftaran terbuka</h2>
        @forelse ($openCompetitions as $competition)
            <article class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">{{ $competition->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $competition->venue }}, {{ $competition->city }}
                            · {{ $competition->start_date->translatedFormat('d M Y') }}
                            @if (! $competition->start_date->equalTo($competition->end_date))
                                – {{ $competition->end_date->translatedFormat('d M Y') }}
                            @endif
                        </p>
                        <p class="mt-2 text-sm font-medium text-amber-800" data-countdown="{{ $competition->registration_closes_at->toIso8601String() }}">
                            Pendaftaran ditutup {{ $competition->registration_closes_at->timezone(config('app.timezone'))->translatedFormat('d M Y H:i') }}
                        </p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <a href="{{ route('public.competitions.schedule', $competition) }}" class="public-btn-secondary">Lihat jadwal</a>
                        <a href="{{ route('register.create', $competition) }}" class="public-btn">Daftar</a>
                    </div>
                </div>
            </article>
        @empty
            <p class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-8 text-sm text-slate-500">
                Saat ini tidak ada kejuaraan yang membuka pendaftaran. Lihat arsip hasil atau cari atlet di menu atas.
            </p>
        @endforelse

        @include('partials.pagination', ['paginator' => $openCompetitions])
    </section>

    <section class="mt-10" id="buku-acara">
        <h2 class="text-xl font-semibold">Buku acara &amp; hasil</h2>
        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">
            Peserta dapat melihat buku acara, buku hasil, club terbaik, dan atlet terbaik tanpa akun. Buku acara tampil setelah seri dibagi. Hasil dan penghargaan tampil setelah dipublikasikan.
        </p>

        @forelse ($liveCompetitions as $competition)
            <article class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">{{ $competition->status->label() }}</p>
                        <h3 class="mt-1 text-lg font-semibold">{{ $competition->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $competition->venue }}, {{ $competition->city }}
                            · {{ $competition->start_date->translatedFormat('d M Y') }}
                        </p>
                    </div>
                    @include('public._books', ['competition' => $competition, 'primary' => 'start-list'])
                </div>
            </article>
        @empty
            @unless ($recentCompetitions->isNotEmpty())
                <p class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-8 text-sm text-slate-500">
                    Buku acara tampil setelah seri dibagi. Buku hasil tampil setelah dipublikasikan. Lihat arsip jika kejuaraan sudah selesai.
                </p>
            @endunless
        @endforelse
    </section>

    <section class="mt-10">
        <h2 class="text-xl font-semibold">Hasil terbaru</h2>
        @forelse ($recentCompetitions as $competition)
            <article class="mt-3 rounded-2xl border border-slate-200 bg-white px-4 py-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold">{{ $competition->name }}</h3>
                        <p class="text-sm text-slate-500">{{ $competition->city }} · {{ $competition->type->label() }}</p>
                    </div>
                    @include('public._books', ['competition' => $competition, 'primary' => 'results'])
                </div>
            </article>
        @empty
            <p class="mt-4 text-sm text-slate-500">Belum ada hasil yang dipublikasikan.</p>
        @endforelse
    </section>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-countdown]').forEach((el) => {
    const target = new Date(el.dataset.countdown).getTime();
    const tick = () => {
        const diff = target - Date.now();
        if (diff <= 0) {
            el.textContent = 'Pendaftaran sudah ditutup';
            return;
        }
        const d = Math.floor(diff / 86400000);
        const h = Math.floor((diff % 86400000) / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        el.textContent = `Hitung mundur penutupan: ${d} hari ${h} jam ${m} menit`;
    };
    tick();
    setInterval(tick, 60000);
});
</script>
@endpush
