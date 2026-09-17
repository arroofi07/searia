@extends('layouts.public')

@section('title', 'Beranda · SeaRIA')
@section('meta_description', 'Kejuaraan renang aktif, pendaftaran, dan hasil terbaru di SeaRIA.')
@section('mainClass', 'w-full')

@section('content')
    @php
        $openTotal = $openCompetitions->total();
        $liveTotal = $liveCompetitions->count();
        $recentTotal = $recentCompetitions->count();
    @endphp

    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: repeating-linear-gradient(90deg, transparent 0, transparent 11.5%, rgba(255,255,255,.55) 11.5%, rgba(255,255,255,.55) 12%);"></div>
        <div class="pointer-events-none absolute -left-24 -top-24 h-80 w-80 rounded-full bg-cyan-400/25 blur-3xl"></div>
        <div class="pointer-events-none absolute -right-10 top-16 h-72 w-72 rounded-full bg-teal-400/20 blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-0 left-1/3 h-56 w-56 rounded-full bg-sky-500/10 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-6xl gap-10 px-4 pb-16 pt-10 sm:pt-14 lg:grid-cols-[minmax(0,1.15fr)_auto] lg:items-center lg:pb-20">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-teal-100">
                    Aquatic SeaRIA
                </p>
                <h1 class="mt-5 max-w-2xl text-4xl font-semibold tracking-tight text-white sm:text-5xl sm:leading-[1.1]">
                    Daftar lomba, lihat buku acara, dan cek hasil
                </h1>
                <p class="mt-4 max-w-xl text-base leading-7 text-teal-50/85 sm:text-lg">
                    Untuk peserta dan orang tua. Tidak perlu membuat akun. Panitia yang memverifikasi data setelah Anda kirim.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                    <a href="{{ route('register.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-white px-6 py-3 text-base font-semibold text-teal-950 shadow-lg shadow-teal-950/20 hover:bg-teal-50">Daftar lomba</a>
                    @if ($featuredStartList)
                        <a href="{{ route('start-list.show', $featuredStartList) }}" class="inline-flex min-h-12 items-center justify-center rounded-full border border-white/25 bg-white/5 px-6 py-3 text-base font-semibold text-white backdrop-blur hover:bg-white/10">Buku acara</a>
                    @endif
                    @if ($featuredResults)
                        <a href="{{ route('results.index', $featuredResults) }}" class="inline-flex min-h-12 items-center justify-center rounded-full border border-white/25 bg-white/5 px-6 py-3 text-base font-semibold text-white backdrop-blur hover:bg-white/10">Buku hasil</a>
                    @endif
                </div>

                <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-sm text-teal-100/80">
                    @if ($featuredResults)
                        <a href="{{ route('results.best-club', $featuredResults) }}" class="underline-offset-4 hover:text-white hover:underline">Club terbaik</a>
                        <a href="{{ route('results.best-swimmers', $featuredResults) }}" class="underline-offset-4 hover:text-white hover:underline">Atlet terbaik</a>
                    @endif
                    <a href="{{ route('public.athletes.search') }}" class="underline-offset-4 hover:text-white hover:underline">Cari hasil atlet</a>
                    <a href="{{ route('archive.index') }}" class="underline-offset-4 hover:text-white hover:underline">Arsip kejuaraan</a>
                </div>
            </div>

            <div class="mx-auto w-full max-w-sm lg:mx-0">
                <div class="rounded-[2rem] border border-white/15 bg-white/10 p-5 shadow-2xl shadow-teal-950/40 backdrop-blur-md">
                    <div class="rounded-3xl bg-white p-5">
                        @include('layouts.partials.brand-mark', ['class' => 'mx-auto h-36 w-auto sm:h-44', 'alt' => 'Aquatic SeaRIA'])
                    </div>
                    <dl class="mt-5 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-2xl bg-white/10 px-2 py-3">
                            <dt class="text-[11px] uppercase tracking-wide text-teal-100/70">Daftar</dt>
                            <dd class="mt-1 text-xl font-semibold tabular-nums">{{ $openTotal }}</dd>
                        </div>
                        <div class="rounded-2xl bg-white/10 px-2 py-3">
                            <dt class="text-[11px] uppercase tracking-wide text-teal-100/70">Berlangsung</dt>
                            <dd class="mt-1 text-xl font-semibold tabular-nums">{{ $liveTotal }}</dd>
                        </div>
                        <div class="rounded-2xl bg-white/10 px-2 py-3">
                            <dt class="text-[11px] uppercase tracking-wide text-teal-100/70">Hasil</dt>
                            <dd class="mt-1 text-xl font-semibold tabular-nums">{{ $recentTotal }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <svg class="relative block w-full text-slate-50" viewBox="0 0 1440 72" preserveAspectRatio="none" aria-hidden="true">
            <path fill="currentColor" d="M0,32 C240,80 480,0 720,32 C960,64 1200,8 1440,40 L1440,72 L0,72 Z"></path>
        </svg>
    </section>

    <div class="bg-slate-50">
        <div class="mx-auto max-w-6xl space-y-12 px-4 pb-16">
            <section class="relative -mt-4 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Cara daftar, singkatnya</h2>
                        <p class="mt-1 text-sm text-slate-500">Tiga langkah. Tanpa akun, tanpa antre di meja panitia.</p>
                    </div>
                    <a href="{{ route('register.index') }}" class="text-sm font-semibold text-teal-700 hover:text-teal-900">Mulai pendaftaran →</a>
                </div>
                <ol class="mt-6 grid gap-4 sm:grid-cols-3">
                    <li class="relative rounded-2xl bg-slate-50 p-5">
                        <span class="text-3xl font-semibold tabular-nums text-teal-700/20">01</span>
                        <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-teal-700">Langkah 1</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">Isi data peserta</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Nama lengkap, L/P, tahun lahir, klub/sekolah, dan kabupaten/kota.</p>
                    </li>
                    <li class="relative rounded-2xl bg-slate-50 p-5">
                        <span class="text-3xl font-semibold tabular-nums text-teal-700/20">02</span>
                        <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-teal-700">Langkah 2</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">Pilih kode acara + catatan waktu</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Catatan waktu (seed) untuk pembagian lintasan. Kosong = NT.</p>
                    </li>
                    <li class="relative rounded-2xl bg-slate-50 p-5">
                        <span class="text-3xl font-semibold tabular-nums text-teal-700/20">03</span>
                        <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-teal-700">Langkah 3</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">Simpan kode REG-…</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Kode itu dipakai jika panitia perlu menghubungi atau memperbaiki data.</p>
                    </li>
                </ol>
            </section>

            <section>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Pendaftaran terbuka</h2>
                        <p class="mt-1 text-sm text-slate-500">Pilih kejuaraan, lalu kirim data peserta sebelum batas waktu.</p>
                    </div>
                    @if ($openTotal > 0)
                        <p class="text-sm font-medium text-teal-800">{{ $openTotal }} kejuaraan bisa didaftarkan</p>
                    @endif
                </div>

                <div class="mt-5 grid gap-4">
                    @forelse ($openCompetitions as $competition)
                        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md sm:p-6">
                            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0">
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">Pendaftaran terbuka</span>
                                    <h3 class="mt-3 text-xl font-semibold tracking-tight text-slate-900">{{ $competition->name }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $competition->venue }}, {{ $competition->city }}
                                        · {{ $competition->start_date->translatedFormat('d M Y') }}
                                        @if (! $competition->start_date->equalTo($competition->end_date))
                                            – {{ $competition->end_date->translatedFormat('d M Y') }}
                                        @endif
                                    </p>
                                    <p class="mt-3 inline-flex rounded-full bg-slate-100 px-3 py-1.5 text-sm font-medium text-amber-900" data-countdown="{{ $competition->registration_closes_at->toIso8601String() }}">
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
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center">
                            <p class="text-sm text-slate-500">
                                Saat ini tidak ada kejuaraan yang membuka pendaftaran. Lihat arsip hasil atau cari atlet di menu atas.
                            </p>
                            <div class="mt-5 flex flex-col items-center justify-center gap-3 sm:flex-row">
                                <a href="{{ route('archive.index') }}" class="public-btn-secondary">Lihat arsip</a>
                                <a href="{{ route('public.athletes.search') }}" class="public-btn-secondary">Cari hasil atlet</a>
                            </div>
                        </div>
                    @endforelse
                </div>

                @include('partials.pagination', ['paginator' => $openCompetitions])
            </section>

            <section id="buku-acara" class="scroll-mt-28">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Buku acara &amp; hasil</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">
                            Peserta dapat melihat buku acara, buku hasil, club terbaik, dan atlet terbaik tanpa akun. Buku acara tampil setelah seri dibagi. Hasil dan penghargaan tampil setelah dipublikasikan.
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    @forelse ($liveCompetitions as $competition)
                        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">{{ $competition->status->label() }}</p>
                            <h3 class="mt-2 text-lg font-semibold tracking-tight text-slate-900">{{ $competition->name }}</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $competition->venue }}, {{ $competition->city }}
                                · {{ $competition->start_date->translatedFormat('d M Y') }}
                            </p>
                            <div class="mt-4">
                                @include('public._books', ['competition' => $competition, 'primary' => 'start-list', 'variant' => 'pills'])
                            </div>
                        </article>
                    @empty
                        @unless ($recentCompetitions->isNotEmpty())
                            <p class="rounded-3xl border border-dashed border-slate-300 bg-white px-5 py-10 text-sm text-slate-500 lg:col-span-2">
                                Buku acara tampil setelah seri dibagi. Buku hasil tampil setelah dipublikasikan. Lihat arsip jika kejuaraan sudah selesai.
                            </p>
                        @endunless
                    @endforelse
                </div>
            </section>

            <section>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Hasil terbaru</h2>
                        <p class="mt-1 text-sm text-slate-500">Peringkat, medali, club terbaik, dan atlet terbaik setelah dipublikasikan.</p>
                    </div>
                    <a href="{{ route('archive.index') }}" class="text-sm font-semibold text-teal-700 hover:text-teal-900">Lihat arsip →</a>
                </div>

                <div class="mt-5 grid gap-3">
                    @forelse ($recentCompetitions as $competition)
                        <article class="rounded-3xl border border-slate-200 bg-white px-5 py-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold tracking-tight text-slate-900">{{ $competition->name }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ $competition->city }} · {{ $competition->type->label() }}</p>
                                </div>
                                @include('public._books', ['competition' => $competition, 'primary' => 'results', 'variant' => 'pills'])
                            </div>
                        </article>
                    @empty
                        <p class="mt-1 text-sm text-slate-500">Belum ada hasil yang dipublikasikan.</p>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl bg-teal-900 px-6 py-8 text-white sm:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-xl">
                        <h2 class="text-2xl font-semibold tracking-tight">Cari hasil seorang atlet</h2>
                        <p class="mt-2 text-sm leading-6 text-teal-50/85">Masukkan nama. Tidak perlu akun. Berguna untuk orang tua, pelatih, dan panitia yang ingin cek cepat.</p>
                    </div>
                    <a href="{{ route('public.athletes.search') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-white px-6 py-3 text-base font-semibold text-teal-950 hover:bg-teal-50">Cari hasil atlet</a>
                </div>
            </section>
        </div>
    </div>
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
