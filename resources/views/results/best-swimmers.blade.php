@extends('layouts.public')

@section('title', 'Atlet terbaik · '.$competition->name)
@section('meta_description', 'Atlet terbaik '.$competition->name.' per jenis kelamin dan kelompok umur')
@section('og_title', 'Atlet terbaik · '.$competition->name)

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Atlet terbaik</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $competition->name }} · juara medali per putra/putri dan kelompok umur</p>
        </div>
        <a href="{{ route('results.best-swimmers.pdf', $competition) }}" class="public-btn-secondary">Unduh PDF</a>
    </div>

    <div class="mt-4">
        @include('public._books', ['competition' => $competition, 'primary' => 'best-swimmers'])
    </div>

    @if ($preview)
        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">Pratinjau panitia — belum dipublikasikan.</div>
    @endif

    @forelse ($groups as $group)
        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3">
                <h2 class="font-medium">{{ $group['gender_label'] }} · {{ $group['age_group_name'] }}</h2>
            </div>
            <table class="stack-table min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Pos</th>
                        <th class="px-3 py-2">Nama</th>
                        <th class="px-3 py-2">Klub</th>
                        <th class="px-3 py-2">Emas</th>
                        <th class="px-3 py-2">Perak</th>
                        <th class="px-3 py-2">Perunggu</th>
                        <th class="px-3 py-2">Poin</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($group['winners'] as $winner)
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-2" data-label="Pos">{{ $winner['position'] }}</td>
                            <td class="px-3 py-2" data-label="Nama">
                                <a href="{{ route('results.athlete', [$competition, $winner['athlete_id']]) }}" class="hover:underline">{{ $winner['athlete_name'] }}</a>
                            </td>
                            <td class="px-3 py-2" data-label="Klub">
                                {{ $winner['club_name'] }}
                                @if ($winner['city'])
                                    <span class="text-xs text-slate-400">· {{ $winner['city'] }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2" data-label="Emas">{{ $winner['gold'] }}</td>
                            <td class="px-3 py-2" data-label="Perak">{{ $winner['silver'] }}</td>
                            <td class="px-3 py-2" data-label="Perunggu">{{ $winner['bronze'] }}</td>
                            <td class="px-3 py-2" data-label="Poin">{{ $winner['points'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @empty
        <p class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-8 text-sm text-slate-500">Belum ada atlet terbaik. Medali belum dihitung.</p>
    @endforelse

    @include('partials.pagination', ['paginator' => $groups])
@endsection
