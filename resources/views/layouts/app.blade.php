<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'SeaRIA')</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3">
                <a href="{{ route('dashboard') }}" class="text-lg font-semibold tracking-tight text-teal-800">SeaRIA</a>
                <nav class="flex flex-wrap items-center gap-3 text-sm">
                    @can('viewAny', App\Models\Competition::class)
                        <a href="{{ route('admin.competitions.index') }}" class="{{ request()->routeIs('admin.competitions.*') ? 'font-semibold text-teal-800' : 'text-slate-600 hover:text-teal-800' }}">Kejuaraan</a>
                        <a href="{{ route('admin.site-pages.index') }}" class="{{ request()->routeIs('admin.site-pages.*') ? 'font-semibold text-teal-800' : 'text-slate-600 hover:text-teal-800' }}">Halaman publik</a>
                        <a href="{{ route('admin.activity-logs.index') }}" class="{{ request()->routeIs('admin.activity-logs.*') ? 'font-semibold text-teal-800' : 'text-slate-600 hover:text-teal-800' }}">Audit</a>
                    @endcan
                    @can('viewAny', App\Models\Club::class)
                        <a href="{{ route('admin.clubs.index') }}" class="{{ request()->routeIs('admin.clubs.*') ? 'font-semibold text-teal-800' : 'text-slate-600 hover:text-teal-800' }}">Klub</a>
                    @endcan
                    @can('viewAny', App\Models\Athlete::class)
                        <a href="{{ route('athletes.index') }}" class="{{ request()->routeIs('athletes.*') || request()->routeIs('admin.athletes.*') ? 'font-semibold text-teal-800' : 'text-slate-600 hover:text-teal-800' }}">Atlet</a>
                    @endcan
                    @if (auth()->user()?->isJuri() || auth()->user()?->managesMasterData())
                        <a href="{{ route('judge.tasks') }}" class="{{ request()->routeIs('judge.*') ? 'font-semibold text-teal-800' : 'text-slate-600 hover:text-teal-800' }}">Tugas juri</a>
                    @endif
                    <a href="{{ route('register.index') }}" class="{{ request()->routeIs('register.*') ? 'font-semibold text-teal-800' : 'text-slate-600 hover:text-teal-800' }}">Form pendaftaran</a>
                </nav>
                <div class="flex items-center gap-3 text-sm">
                    <span class="text-slate-500">{{ auth()->user()?->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-slate-600 hover:text-red-700">Keluar</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('delete'))
                <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ $errors->first('delete') }}
                </div>
            @endif

            @yield('content')
        </main>
        @stack('scripts')
    </body>
</html>
