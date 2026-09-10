<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
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
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/public.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="relative sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('home') }}" class="flex min-h-11 items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-700 text-sm font-bold text-white">SR</span>
                <span class="leading-tight">
                    <span class="block text-base font-semibold tracking-tight text-teal-900">SeaRIA</span>
                    <span class="block text-[11px] font-medium text-slate-500">Kejuaraan renang</span>
                </span>
            </a>

            <div class="flex items-center gap-2">
                <a href="{{ route('register.index') }}" class="inline-flex min-h-10 items-center rounded-full bg-teal-700 px-3.5 py-2 text-sm font-semibold text-white hover:bg-teal-800 md:hidden">
                    Daftar
                </a>
                <button type="button" class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-700 md:hidden" data-public-nav-toggle aria-expanded="false" aria-controls="public-nav">
                    <span class="sr-only">Buka menu</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true">
                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>
            </div>

            <nav id="public-nav" data-public-nav-panel class="border-b border-slate-200 bg-white px-4 py-4 shadow-lg md:border-0 md:bg-transparent md:p-0 md:shadow-none">
                <div class="flex flex-col gap-1 text-base md:ml-auto md:flex-row md:items-center md:gap-1 md:text-sm">
                    <a href="{{ route('home') }}" class="rounded-lg px-3 py-2.5 {{ request()->routeIs('home') ? 'bg-teal-50 font-semibold text-teal-900' : 'text-slate-600 hover:bg-slate-50 hover:text-teal-800' }}">Beranda</a>
                    <a href="{{ route('register.index') }}" class="rounded-lg px-3 py-2.5 {{ request()->routeIs('register.*') ? 'bg-teal-50 font-semibold text-teal-900' : 'text-slate-600 hover:bg-slate-50 hover:text-teal-800' }}">Daftar lomba</a>
                    <a href="{{ route('archive.index') }}" class="rounded-lg px-3 py-2.5 {{ request()->routeIs('archive.*') ? 'bg-teal-50 font-semibold text-teal-900' : 'text-slate-600 hover:bg-slate-50 hover:text-teal-800' }}">Arsip</a>
                    <a href="{{ route('public.athletes.search') }}" class="rounded-lg px-3 py-2.5 {{ request()->routeIs('public.athletes.*') ? 'bg-teal-50 font-semibold text-teal-900' : 'text-slate-600 hover:bg-slate-50 hover:text-teal-800' }}">Cari atlet</a>
                    <a href="{{ route('about') }}" class="rounded-lg px-3 py-2.5 {{ request()->routeIs('about') ? 'bg-teal-50 font-semibold text-teal-900' : 'text-slate-600 hover:bg-slate-50 hover:text-teal-800' }}">Tentang</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-teal-800">Dasbor</a>
                    @else
                        <a href="{{ route('login') }}" class="mt-2 rounded-xl bg-slate-900 px-4 py-2.5 text-center font-semibold text-white hover:bg-slate-800 md:mt-0 md:ml-2 md:rounded-full md:px-3 md:py-1.5 md:text-sm">Masuk panitia</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6 sm:py-8">
        @if (session('status'))
            <div class="mb-4 rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-8 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="font-semibold text-slate-800">SeaRIA</p>
                <p class="mt-1">&copy; {{ date('Y') }} Sistem informasi kejuaraan renang</p>
            </div>
            <nav class="flex flex-wrap gap-x-5 gap-y-2">
                <a href="{{ route('register.index') }}" class="hover:text-teal-800">Daftar lomba</a>
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
