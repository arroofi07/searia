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
        <div class="flex min-h-screen">
            <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden"></div>

            <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 flex min-h-screen w-64 -translate-x-full flex-col overflow-y-auto bg-slate-900 transition-transform lg:static lg:translate-x-0">
                @include('layouts.partials.sidebar')
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 lg:hidden">
                    <button type="button" id="sidebar-toggle" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700" aria-controls="admin-sidebar" aria-expanded="false">
                        Menu
                    </button>
                    <a href="{{ route('dashboard') }}" class="font-semibold text-teal-800">SeaRIA</a>
                </header>

                <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6">
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
            </div>
        </div>

        <script>
            (() => {
                const sidebar = document.getElementById('admin-sidebar');
                const backdrop = document.getElementById('sidebar-backdrop');
                const toggle = document.getElementById('sidebar-toggle');
                if (!sidebar || !backdrop || !toggle) {
                    return;
                }

                const setOpen = (open) => {
                    sidebar.classList.toggle('-translate-x-full', !open);
                    backdrop.classList.toggle('hidden', !open);
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                };

                toggle.addEventListener('click', () => {
                    setOpen(sidebar.classList.contains('-translate-x-full'));
                });
                backdrop.addEventListener('click', () => setOpen(false));
            })();
        </script>
        @stack('scripts')
    </body>
</html>
