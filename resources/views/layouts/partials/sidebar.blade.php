@php
    use App\Support\AdminNavigation;

    $user = auth()->user();
    $manages = $user?->managesMasterData() ?? false;
    $competition = $manages ? AdminNavigation::competition() : null;
    $competitions = $manages ? AdminNavigation::competitions() : collect();
    $current = AdminNavigation::currentKey();
@endphp

<div class="flex h-full flex-col">
    <div class="flex items-center gap-2 px-4 py-4">
        <a href="{{ route('dashboard') }}" class="text-lg font-semibold tracking-tight text-white">SeaRIA</a>
    </div>

    <nav class="flex-1 space-y-4 overflow-y-auto px-3 pb-4">
        @if ($manages)
            @if ($competitions->isNotEmpty())
                <label class="sr-only" for="sidebar-competition">Pilih acara</label>
                <select id="sidebar-competition" class="w-full rounded-md border border-slate-700 bg-slate-800 px-2 py-2 text-sm text-white" onchange="window.location = this.value">
                    @foreach ($competitions as $option)
                        <option value="{{ route('admin.competitions.show', $option) }}" @selected($competition?->id === $option->id)>
                            {{ $option->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            <div class="space-y-0.5">
                <a href="{{ route('admin.competitions.index') }}" class="{{ AdminNavigation::linkClass($current === 'dasbor') }}">Dasbor</a>
                @can('viewAny', App\Models\Registration::class)
                    <a href="{{ AdminNavigation::url('admin.registrations.index') }}" class="{{ AdminNavigation::linkClass($current === 'registrations') }}">Pendaftaran</a>
                @endcan
                    <a href="{{ AdminNavigation::url('admin.seeding.index') }}" class="{{ AdminNavigation::linkClass($current === 'seeding') }}">Pembagian seri</a>
                <a href="{{ AdminNavigation::url('admin.start-list.index') }}" class="{{ AdminNavigation::linkClass($current === 'start-list') }}">Buku acara</a>
                <a href="{{ AdminNavigation::url('admin.results.index') }}" class="{{ AdminNavigation::linkClass($current === 'results') }}">Hasil</a>
                @can('viewAny', App\Models\Club::class)
                    <a href="{{ route('admin.clubs.index') }}" class="{{ AdminNavigation::linkClass(request()->routeIs('admin.clubs.*')) }}">Klub</a>
                @endcan
                @can('viewAny', App\Models\Athlete::class)
                    <a href="{{ route('athletes.index') }}" class="{{ AdminNavigation::linkClass(request()->routeIs('athletes.*', 'admin.athletes.*')) }}">Atlet</a>
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
