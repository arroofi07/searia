@extends('layouts.app')

@section('title', 'Pilih acara')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Pilih acara</h1>
            <p class="text-sm text-slate-500">Buka acara untuk mengelola pendaftaran, pembagian seri, dan hasil.</p>
        </div>
        @can('create', App\Models\Competition::class)
            <a href="{{ route('admin.competitions.create') }}" class="rounded-md bg-teal-700 px-4 py-2 text-center text-sm font-medium text-white hover:bg-teal-800">Tambah acara</a>
        @endcan
    </div>

    @if ($competitions->isEmpty())
        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-950">
            <p class="font-medium">Belum ada acara</p>
            <p class="mt-1">
                Menu <strong>Pendaftaran</strong>, <strong>Import Excel</strong>, dan lainnya membutuhkan minimal satu acara.
                @can('create', App\Models\Competition::class)
                    Klik <a href="{{ route('admin.competitions.create') }}" class="font-medium text-teal-800 hover:underline">Tambah acara</a> untuk memulai.
                @else
                    Minta Super Admin membuat acara terlebih dahulu.
                @endcan
            </p>
        </div>
    @endif

    @if (auth()->user()?->managesMasterData())
        <label class="mt-6 block rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" for="maintenance-cache-panel">
            <span class="font-medium text-slate-800">Pemeliharaan (tanpa terminal)</span>
            — setelah upload berkas lewat cPanel, bersihkan cache agar logo/CSS/beranda ikut terbaru.
        </label>
        <form id="maintenance-cache-panel" method="POST" action="{{ route('admin.maintenance.clear-cache') }}" class="mt-2">
            @csrf
            <button type="submit" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Bersihkan cache aplikasi</button>
        </form>
    @endif

    <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Nama</th>
                    <th class="px-4 py-3 font-medium">Tanggal</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Grup</th>
                    <th class="px-4 py-3 font-medium">Nomor</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($competitions as $competition)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @if ($competition->logoUrl())
                                    <img src="{{ $competition->logoUrl() }}" alt="" class="h-8 w-8 shrink-0 rounded border border-slate-200 bg-white object-contain p-0.5">
                                @endif
                                <a href="{{ route('admin.competitions.show', $competition) }}" class="font-medium text-teal-800 hover:underline">{{ $competition->name }}</a>
                            </div>
                            <div class="text-xs text-slate-500">{{ $competition->city }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $competition->start_date->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $competition->status->label() }}</td>
                        <td class="px-4 py-3">{{ $competition->age_groups_count }}</td>
                        <td class="px-4 py-3">{{ $competition->events_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada acara.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.pagination', ['paginator' => $competitions])
@endsection
