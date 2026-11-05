<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a2e39">
    <title>@yield('title', 'SeaRIA')</title>
    @include('layouts.partials.favicon')
    <meta name="description" content="@yield('meta_description', 'Sistem informasi kejuaraan renang SeaRIA — pendaftaran, buku acara, dan hasil lomba.')">
    <meta property="og:title" content="@yield('og_title', trim($__env->yieldContent('title')).' · SeaRIA')">
    <meta property="og:description" content="@yield('meta_description', 'Sistem informasi kejuaraan renang SeaRIA — pendaftaran, buku acara, dan hasil lomba.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/public.js'])
    <link rel="stylesheet" href="{{ \App\Support\PublicAssets::cssUrl() }}">
    <link rel="stylesheet" href="{{ rtrim(request()->getBasePath(), '/') }}/css/public.css?v={{ is_file(public_path('css/public.css')) ? filemtime(public_path('css/public.css')) : '1' }}">
    @stack('head')
</head>
<body class="deck @yield('bodyClass')">
    <a class="skip" href="#isi">Langsung ke isi</a>
    <header class="site-head">
        <div class="site-head-bar">
            <a href="{{ route('home') }}" class="site-logo">
                @include('layouts.partials.brand-mark', ['class' => '', 'alt' => 'Aquatic SeaRIA'])
            </a>

            <div class="site-tools">
                <a href="{{ route('register.index') }}" class="site-cta">Daftar</a>
                <button type="button" class="site-menu" data-public-nav-toggle aria-expanded="false" aria-controls="public-nav">
                    <span class="sr-only">Buka menu</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>
            </div>

            <nav id="public-nav" data-public-nav-panel class="site-nav">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-on' : '' }}">Beranda</a>
                <a href="{{ route('register.index') }}" class="{{ request()->routeIs('register.*') ? 'is-on' : '' }}">Daftar lomba</a>
                <a href="{{ route('home') }}#buku-acara" class="{{ request()->routeIs('start-list.*', 'results.*') ? 'is-on' : '' }}">Buku &amp; hasil</a>
                <a href="{{ route('archive.index') }}" class="{{ request()->routeIs('archive.*') ? 'is-on' : '' }}">Arsip</a>
                <a href="{{ route('public.athletes.search') }}" class="{{ request()->routeIs('public.athletes.*') ? 'is-on' : '' }}">Cari atlet</a>
                <a href="{{ route('about') }}" class="{{ request()->routeIs('about', 'terms') ? 'is-on' : '' }}">Tentang</a>
                @auth
                    <a href="{{ route('dashboard') }}">Dasbor</a>
                @else
                    <a href="{{ route('login') }}" class="site-login">Masuk panitia</a>
                @endauth
            </nav>
        </div>
    </header>

    <main id="isi" class="{{ trim($__env->yieldContent('mainClass')) ?: 'site-main' }}">
        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="site-foot">
        <div class="site-foot-grid">
            <div class="site-brand">
                @include('layouts.partials.brand-mark', ['class' => '', 'alt' => 'Aquatic SeaRIA'])
                <div>
                    <p><strong>Aquatic SeaRIA</strong></p>
                    <p>&copy; {{ date('Y') }} Sistem informasi kejuaraan renang. Pendaftaran, buku acara, dan hasil dalam satu tempat.</p>
                </div>
            </div>
            <nav aria-label="Tautan peserta">
                <p class="kicker">Peserta</p>
                <a href="{{ route('register.index') }}">Daftar lomba</a>
                <a href="{{ route('home') }}#buku-acara">Buku &amp; hasil</a>
                <a href="{{ route('public.athletes.search') }}">Cari hasil atlet</a>
                <a href="{{ route('archive.index') }}">Arsip hasil</a>
            </nav>
            <nav aria-label="Informasi">
                <p class="kicker">Informasi</p>
                <a href="{{ route('about') }}">Pengenalan</a>
                <a href="{{ route('terms') }}">Syarat &amp; ketentuan</a>
                <a href="{{ route('sitemap') }}">Peta situs</a>
                <a href="{{ route('login') }}">Masuk panitia</a>
            </nav>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
