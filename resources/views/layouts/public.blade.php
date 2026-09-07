<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SeaRIA')</title>
    <meta name="description" content="@yield('meta_description', 'Sistem informasi kejuaraan renang SeaRIA — pendaftaran, buku acara, dan hasil lomba.')">
    <meta property="og:title" content="@yield('og_title', trim($__env->yieldContent('title')).' · SeaRIA')">
    <meta property="og:description" content="@yield('meta_description', 'Sistem informasi kejuaraan renang SeaRIA — pendaftaran, buku acara, dan hasil lomba.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight text-teal-800">SeaRIA</a>
            <nav class="flex flex-wrap items-center gap-3 text-sm">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'font-semibold text-teal-800' : 'text-slate-600 hover:text-teal-800' }}">Beranda</a>
                <a href="{{ route('archive.index') }}" class="{{ request()->routeIs('archive.*') ? 'font-semibold text-teal-800' : 'text-slate-600 hover:text-teal-800' }}">Arsip</a>
                <a href="{{ route('public.athletes.search') }}" class="{{ request()->routeIs('public.athletes.*') ? 'font-semibold text-teal-800' : 'text-slate-600 hover:text-teal-800' }}">Cari atlet</a>
                <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'font-semibold text-teal-800' : 'text-slate-600 hover:text-teal-800' }}">Tentang</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="text-slate-600 hover:text-teal-800">Dasbor</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-md bg-teal-700 px-3 py-1.5 font-medium text-white hover:bg-teal-800">Masuk</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6">
        @if (session('status'))
            <div class="mb-4 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="mt-12 border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-6 text-sm text-slate-600">
            <p>&copy; {{ date('Y') }} SeaRIA</p>
            <nav class="flex flex-wrap gap-4">
                <a href="{{ route('about') }}" class="hover:text-teal-800">Pengenalan</a>
                <a href="{{ route('terms') }}" class="hover:text-teal-800">Syarat &amp; ketentuan</a>
                <a href="{{ route('archive.index') }}" class="hover:text-teal-800">Arsip hasil</a>
                <a href="{{ route('sitemap') }}" class="hover:text-teal-800">Peta situs</a>
            </nav>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
