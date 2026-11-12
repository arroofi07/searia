@extends('layouts.public')

@section('title', 'Beranda · SeaRIA')
@section('meta_description', 'Kejuaraan renang aktif, pendaftaran, dan hasil terbaru di SeaRIA.')
@section('mainClass', 'w-full')
@section('bodyClass', 'deck-home')

@push('head')
    <link rel="stylesheet" href="{{ rtrim(request()->getBasePath(), '/') }}/css/home.css?v={{ is_file(public_path('css/home.css')) ? filemtime(public_path('css/home.css')) : '1' }}">
@endpush

@section('content')
    @php
        $openTotal = $openCompetitions->total();
        $liveTotal = $liveCompetitions->count();
        $recentTotal = $recentCompetitions->count();
    @endphp

    <div class="deck">
        <section class="deck-hero">
            <div class="deck-hero-inner">
                <p class="deck-kicker">
                    <span>Aquatic SeaRIA</span>
                    <span class="deck-kicker-rule" aria-hidden="true"></span>
                    <span>Kejuaraan renang</span>
                </p>

                <div class="deck-hero-grid">
                    <div>
                        <h1 class="deck-title">
                            Daftar lomba, lihat buku acara, dan cek hasil
                        </h1>
                        <p class="deck-lead">
                            Untuk peserta dan orang tua. Tidak perlu membuat akun. Panitia yang memverifikasi data setelah Anda kirim.
                        </p>
                        <div class="deck-actions">
                            <a href="{{ route('register.index') }}" class="deck-btn deck-btn-signal">Daftar lomba</a>
                            @if ($featuredStartList)
                                <a href="{{ route('start-list.show', $featuredStartList) }}" class="deck-btn deck-btn-line">Buku acara</a>
                            @endif
                            @if ($featuredResults)
                                <a href="{{ route('results.index', $featuredResults) }}" class="deck-btn deck-btn-line">Buku hasil</a>
                            @endif
                        </div>
                        <ul class="deck-quick">
                            @if ($featuredResults?->hasPublicAwards())
                                <li><a href="{{ route('results.best-club', $featuredResults) }}">Club terbaik</a></li>
                                <li><a href="{{ route('results.best-swimmers', $featuredResults) }}">Atlet terbaik</a></li>
                            @endif
                            <li><a href="{{ route('public.athletes.search') }}">Cari hasil atlet</a></li>
                            <li><a href="{{ route('archive.index') }}">Arsip kejuaraan</a></li>
                        </ul>
                    </div>

                    <div class="deck-hero-card">
                        @include('layouts.partials.event-logo', [
                            'competition' => $featuredCompetition,
                            'class' => '',
                            'alt' => $featuredCompetition?->name ?? 'Aquatic SeaRIA',
                        ])
                        <dl class="deck-stats">
                            <div>
                                <dt>Daftar</dt>
                                <dd>{{ $openTotal }}</dd>
                            </div>
                            <div>
                                <dt>Berlangsung</dt>
                                <dd>{{ $liveTotal }}</dd>
                            </div>
                            <div>
                                <dt>Hasil</dt>
                                <dd>{{ $recentTotal }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
            <svg class="wave" viewBox="0 0 1440 56" preserveAspectRatio="none" aria-hidden="true">
                <path fill="#f5f8fa" d="M0,28 C240,56 480,0 720,22 C960,44 1200,8 1440,28 L1440,56 L0,56 Z"></path>
            </svg>
        </section>

        <div class="deck-sheet">
            <section class="deck-steps">
                <div class="deck-block-head">
                    <p class="deck-index">Cara daftar</p>
                    <div class="deck-note-row">
                        <h2>Tiga langkah, tanpa akun</h2>
                        <a href="{{ route('register.index') }}" class="deck-more">Mulai pendaftaran</a>
                    </div>
                    <p class="deck-note">Tanpa antre di meja panitia. Isi data, pilih nomor, simpan kode.</p>
                </div>
                <ol class="deck-steps-list">
                    <li>
                        <span class="deck-step-no">01</span>
                        <h3>Isi data peserta</h3>
                        <p>Nama lengkap, L/P, tahun lahir, klub/sekolah, dan kabupaten/kota.</p>
                    </li>
                    <li>
                        <span class="deck-step-no">02</span>
                        <h3>Pilih kode acara + catatan waktu</h3>
                        <p>Catatan waktu (seed) untuk pembagian lintasan. Kosong = NT.</p>
                    </li>
                    <li>
                        <span class="deck-step-no">03</span>
                        <h3>Simpan kode REG-…</h3>
                        <p>Kode itu dipakai jika panitia perlu menghubungi atau memperbaiki data.</p>
                    </li>
                </ol>
            </section>

            <section class="deck-open deck-follow">
                <div class="deck-block-head">
                    <p class="deck-index">Pendaftaran</p>
                    <h2>Pendaftaran terbuka</h2>
                    @if ($openTotal > 0)
                        <p class="deck-note">{{ $openTotal }} kejuaraan bisa didaftarkan</p>
                    @endif
                    <p class="deck-note">Pilih kejuaraan, lalu kirim data peserta sebelum batas waktu.</p>
                </div>

                @forelse ($openCompetitions as $competition)
                    <article class="deck-meet">
                        <div class="deck-meet-no" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                        @include('layouts.partials.event-logo', [
                            'competition' => $competition,
                            'class' => 'meet-logo deck-meet-logo',
                            'alt' => $competition->name,
                        ])
                        <div>
                            <p class="deck-stamp">Pendaftaran terbuka</p>
                            <h3>{{ $competition->name }}</h3>
                            <p class="deck-meta">
                                {{ $competition->venue }}, {{ $competition->city }}
                                · {{ $competition->start_date->translatedFormat('d M Y') }}
                                @if (! $competition->start_date->equalTo($competition->end_date))
                                    – {{ $competition->end_date->translatedFormat('d M Y') }}
                                @endif
                            </p>
                            <p class="deck-count" data-countdown="{{ $competition->registration_closes_at->toIso8601String() }}">
                                Pendaftaran ditutup {{ $competition->registration_closes_at->timezone(config('app.timezone'))->translatedFormat('d M Y H:i') }}
                            </p>
                        </div>
                        <div class="deck-meet-actions deck-actions">
                            <a href="{{ route('public.competitions.schedule', $competition) }}" class="deck-btn deck-btn-ghost">Lihat jadwal</a>
                            <a href="{{ route('register.create', $competition) }}" class="deck-btn deck-btn-signal">Daftar</a>
                        </div>
                    </article>
                @empty
                    <div class="deck-empty">
                        <p>Saat ini tidak ada kejuaraan yang membuka pendaftaran. Lihat arsip hasil atau cari atlet di menu atas.</p>
                        <div class="deck-actions">
                            <a href="{{ route('archive.index') }}" class="deck-btn deck-btn-ghost">Lihat arsip</a>
                            <a href="{{ route('public.athletes.search') }}" class="deck-btn deck-btn-ghost">Cari hasil atlet</a>
                        </div>
                    </div>
                @endforelse

                <div class="deck-pager">
                    @include('partials.pagination', ['paginator' => $openCompetitions])
                </div>
            </section>

            <section id="buku-acara" class="deck-books deck-follow">
                <div class="deck-block-head">
                    <p class="deck-index">Buku &amp; hasil</p>
                    <h2>Buku acara &amp; hasil</h2>
                    <p class="deck-note">
                        Peserta dapat melihat buku acara, buku hasil, club terbaik, dan atlet terbaik tanpa akun. Buku acara tampil setelah seri dibagi. Buku hasil tampil sejak hari lomba (sementara jika belum dipublikasikan). Club terbaik dan atlet terbaik tampil setelah dipublikasikan.
                    </p>
                </div>

                <div class="deck-live-grid">
                    @forelse ($liveCompetitions as $competition)
                        <article class="deck-live">
                            @include('layouts.partials.event-logo', [
                                'competition' => $competition,
                                'class' => 'meet-logo deck-meet-logo',
                                'alt' => $competition->name,
                            ])
                            <p class="deck-status">{{ $competition->status->label() }}</p>
                            <h3>{{ $competition->name }}</h3>
                            <p class="deck-meta">
                                {{ $competition->venue }}, {{ $competition->city }}
                                · {{ $competition->start_date->translatedFormat('d M Y') }}
                            </p>
                            @include('public._books', ['competition' => $competition, 'primary' => 'start-list', 'variant' => 'deck'])
                        </article>
                    @empty
                        @unless ($recentCompetitions->isNotEmpty())
                            <p class="deck-empty">
                                Buku acara tampil setelah seri dibagi. Buku hasil tampil sejak hari lomba. Lihat arsip jika kejuaraan sudah dipublikasikan.
                            </p>
                        @endunless
                    @endforelse
                </div>
            </section>

            <div class="deck-split">
                <section>
                    <div class="deck-block-head">
                        <p class="deck-index">Hasil</p>
                        <div class="deck-note-row">
                            <h2>Hasil terbaru</h2>
                            <a href="{{ route('archive.index') }}" class="deck-more">Lihat arsip</a>
                        </div>
                        <p class="deck-note">Peringkat, medali, club terbaik, dan atlet terbaik. Buku hasil bisa dilihat sejak hari lomba; penghargaan resmi setelah dipublikasikan.</p>
                    </div>

                    @forelse ($recentCompetitions as $competition)
                        <article class="deck-result">
                            <div class="deck-result-no" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                            @include('layouts.partials.event-logo', [
                                'competition' => $competition,
                                'class' => 'meet-logo deck-meet-logo',
                                'alt' => $competition->name,
                            ])
                            <div>
                                <h3>{{ $competition->name }}</h3>
                                <p class="deck-meta">{{ $competition->city }} · {{ $competition->type->label() }}</p>
                            </div>
                            @include('public._books', ['competition' => $competition, 'primary' => 'results', 'variant' => 'deck'])
                        </article>
                    @empty
                        <p class="deck-empty">Belum ada hasil yang dipublikasikan.</p>
                    @endforelse
                </section>

                <section class="deck-find">
                    <p class="deck-index">Cari atlet</p>
                    <h2>Cari hasil seorang atlet</h2>
                    <p>Masukkan nama. Tidak perlu akun. Berguna untuk orang tua, pelatih, dan panitia yang ingin cek cepat.</p>
                    <a href="{{ route('public.athletes.search') }}" class="deck-btn">Cari hasil atlet</a>
                </section>
            </div>

            <div class="deck-end"></div>
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
