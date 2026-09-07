@extends('layouts.public')

@section('title', 'Beranda · SeaRIA')
@section('meta_description', 'Kejuaraan renang aktif, pendaftaran, dan hasil terbaru di SeaRIA.')

@section('content')
    <section class="rounded-2xl bg-gradient-to-br from-teal-800 to-slate-900 px-6 py-10 text-white sm:px-10">
        <p class="text-sm uppercase tracking-wide text-teal-100">SeaRIA</p>
        <h1 class="mt-2 max-w-2xl text-3xl font-semibold tracking-tight sm:text-4xl">Sistem informasi kejuaraan renang</h1>
        <p class="mt-3 max-w-xl text-sm text-teal-50/90 sm:text-base">Pendaftaran, buku acara, dan hasil resmi dalam satu tempat.</p>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('public.athletes.search') }}" class="rounded-md bg-white px-4 py-2 text-sm font-medium text-teal-900 hover:bg-teal-50">Cari hasil atlet</a>
            <a href="{{ route('archive.index') }}" class="rounded-md border border-white/40 px-4 py-2 text-sm font-medium text-white hover:bg-white/10">Arsip kejuaraan</a>
        </div>
    </section>

    <section class="mt-10">
        <h2 class="text-xl font-semibold">Pendaftaran terbuka</h2>
        @forelse ($openCompetitions as $competition)
            <article class="mt-4 rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h3 class="text-lg font-medium">{{ $competition->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $competition->venue }}, {{ $competition->city }}
                            · {{ $competition->start_date->translatedFormat('d M Y') }}
                            @if (! $competition->start_date->equalTo($competition->end_date))
                                – {{ $competition->end_date->translatedFormat('d M Y') }}
                            @endif
                        </p>
                        <p class="mt-2 text-sm text-amber-800" data-countdown="{{ $competition->registration_closes_at->toIso8601String() }}">
                            Pendaftaran ditutup {{ $competition->registration_closes_at->timezone(config('app.timezone'))->translatedFormat('d M Y H:i') }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('public.competitions.schedule', $competition) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Jadwal</a>
                        <a href="{{ route('public.competitions.fees', $competition) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Biaya</a>
                        @auth
                            <a href="{{ route('registrations.create', $competition) }}" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Daftar</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Masuk untuk daftar</a>
                        @endauth
                    </div>
                </div>
            </article>
        @empty
            <p class="mt-4 rounded-xl border border-dashed border-slate-300 bg-white px-5 py-8 text-sm text-slate-500">
                Saat ini tidak ada kejuaraan yang membuka pendaftaran. Lihat arsip hasil atau cari atlet di menu atas.
            </p>
        @endforelse
    </section>

    <section class="mt-10">
        <h2 class="text-xl font-semibold">Hasil terbaru</h2>
        @forelse ($recentCompetitions as $competition)
            <article class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3">
                <div>
                    <h3 class="font-medium">{{ $competition->name }}</h3>
                    <p class="text-sm text-slate-500">{{ $competition->city }} · {{ $competition->type->label() }}</p>
                </div>
                <a href="{{ route('results.index', $competition) }}" class="text-sm font-medium text-teal-800 hover:underline">Lihat hasil</a>
            </article>
        @empty
            <p class="mt-4 text-sm text-slate-500">Belum ada hasil yang dipublikasikan.</p>
        @endforelse
    </section>
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
