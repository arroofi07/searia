@php
    use App\Support\AdminNavigation;

    $user = auth()->user();
    $manages = $user?->managesMasterData() ?? false;
    $activeCompetition = $manages ? AdminNavigation::competition() : null;
    $competitions = $manages ? AdminNavigation::competitions() : collect();
    $current = AdminNavigation::currentKey();
@endphp

<div class="flex h-full flex-col">
    <div class="flex items-center gap-2 px-4 py-4">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-xl bg-white px-2 py-1.5">
            @include('layouts.partials.brand-mark', ['class' => 'h-14 w-auto', 'alt' => 'Aquatic SeaRIA'])
        </a>
    </div>

    <nav class="flex-1 space-y-4 overflow-y-auto px-3 pb-4">
        @if ($manages)
            @if ($competitions->isNotEmpty())
                <label class="sr-only" for="sidebar-competition">Pilih acara</label>
                <select id="sidebar-competition" class="w-full rounded-md border border-slate-700 bg-slate-800 px-2 py-2 text-sm text-white" onchange="window.location = this.value">
                    @foreach ($competitions as $option)
                        <option value="{{ route('admin.competitions.show', $option) }}" @selected($activeCompetition?->id === $option->id)>
                            {{ $option->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            @unless ($activeCompetition)
                <p class="mb-2 rounded-md border border-slate-700 bg-slate-800 px-2 py-2 text-xs leading-5 text-slate-400">
                    Belum ada acara aktif. <strong class="text-slate-200">Import Excel</strong> tetap bisa dibuka; menu lain aktif setelah ada acara.
                </p>
            @endunless

            <div class="space-y-0.5">
                <a href="{{ route('admin.competitions.index') }}" class="{{ AdminNavigation::linkClass($current === 'dasbor') }}">Dasbor</a>
                @can('viewAny', App\Models\Registration::class)
                    @include('layouts.partials.sidebar-nav-link', [
                        'label' => 'Pendaftaran',
                        'routeName' => 'admin.registrations.index',
                        'active' => $current === 'registrations',
                        'activeCompetition' => $activeCompetition,
                    ])
                    @include('layouts.partials.sidebar-nav-link', [
                        'label' => 'Naik kelas',
                        'routeName' => 'admin.age-group-promotions.index',
                        'active' => $current === 'naik-kelas',
                        'activeCompetition' => $activeCompetition,
                    ])
                @endcan
                @can('viewAny', App\Models\ImportBatch::class)
                    <a href="{{ route('admin.imports.entry') }}" class="{{ AdminNavigation::linkClass($current === 'imports') }}">Import Excel</a>
                @endcan
                @include('layouts.partials.sidebar-nav-link', [
                    'label' => 'Pembagian seri',
                    'routeName' => 'admin.seeding.index',
                    'active' => $current === 'seeding',
                    'activeCompetition' => $activeCompetition,
                ])
                @include('layouts.partials.sidebar-nav-link', [
                    'label' => 'Buku acara',
                    'routeName' => 'admin.start-list.index',
                    'active' => $current === 'start-list',
                    'activeCompetition' => $activeCompetition,
                ])
                @include('layouts.partials.sidebar-nav-link', [
                    'label' => 'Hasil',
                    'routeName' => 'admin.results.index',
                    'active' => $current === 'results',
                    'activeCompetition' => $activeCompetition,
                ])
                @can('viewAny', App\Models\Club::class)
                    <a href="{{ route('admin.clubs.index') }}" class="{{ AdminNavigation::linkClass(request()->routeIs('admin.clubs.*')) }}">Klub</a>
                @endcan
                @can('viewAny', App\Models\Athlete::class)
                    <a href="{{ route('athletes.index') }}" class="{{ AdminNavigation::linkClass(request()->routeIs('athletes.*', 'admin.athletes.*')) }}">Atlet</a>
                @endcan
                @can('viewAny', App\Models\User::class)
                    <a href="{{ route('admin.users.index') }}" class="{{ AdminNavigation::linkClass(request()->routeIs('admin.users.*')) }}">Akun</a>
                @endcan
            </div>
        @endif

        @if ($user?->isJuri())
            <div class="space-y-0.5">
                <a href="{{ route('judge.tasks') }}" class="{{ AdminNavigation::linkClass(request()->routeIs('judge.*')) }}">Tugas juri</a>
            </div>
        @endif
    </nav>

    <div class="border-t border-slate-800 px-4 py-4">
        <p class="truncate text-sm font-medium text-white">{{ $user?->name }}</p>
        <p class="text-xs text-slate-500">{{ $user?->role->label() }}</p>
        @if ($manages)
            <a href="{{ route('admin.site-pages.index') }}" class="mt-3 block text-sm text-slate-400 hover:text-white">Halaman publik</a>
            <a href="{{ route('admin.activity-logs.index') }}" class="mt-1 block text-sm text-slate-400 hover:text-white">Audit</a>
        @endif
        <a href="{{ route('home') }}" class="mt-3 block text-sm text-slate-400 hover:text-white">Situs publik</a>
        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="text-sm text-slate-400 hover:text-red-300">Keluar</button>
        </form>
    </div>
</div>
