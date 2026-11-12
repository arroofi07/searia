@extends('layouts.public')

@section('title', 'Klasemen klub · '.$competition->name)
@section('meta_description', 'Klasemen medali klub '.$competition->name)

@section('content')
    <div class="mast-row">
        <div>
            @include('public._mast', [
                'competition' => $competition,
                'back' => route('results.index', $competition),
                'backLabel' => 'Hasil',
                'kicker' => 'Penghargaan',
                'title' => 'Klasemen klub',
                'meta' => $competition->name.' · diurutkan emas, perak, perunggu',
            ])
        </div>
        <div class="toolbar">
            @include('public._books', ['competition' => $competition, 'primary' => 'best-club'])
            <a href="{{ route('results.best-club.pdf', $competition) }}" class="public-btn-secondary">Unduh PDF club terbaik</a>
        </div>
    </div>
    @if ($preview)
        <div class="note">Pratinjau panitia</div>
    @endif

    <div class="program">
        <table class="stack-table min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-3 py-2">#</th>
                    <th class="px-3 py-2">Klub</th>
                    <th class="px-3 py-2">Emas</th>
                    <th class="px-3 py-2">Perak</th>
                    <th class="px-3 py-2">Perunggu</th>
                    <th class="px-3 py-2">Total</th>
                    <th class="px-3 py-2">Peserta</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $index => $row)
                    <tr class="border-t border-slate-100 {{ $row['has_medals'] ? '' : 'text-slate-500' }}">
                        <td class="px-3 py-2" data-label="Peringkat">{{ $rows->firstItem() + $index }}</td>
                        <td class="px-3 py-2" data-label="Klub">
                            {{ $row['club_name'] }}
                            @if ($row['city'])
                                <span class="text-xs text-slate-400">· {{ $row['city'] }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2" data-label="Emas">{{ $row['gold'] }}</td>
                        <td class="px-3 py-2" data-label="Perak">{{ $row['silver'] }}</td>
                        <td class="px-3 py-2" data-label="Perunggu">{{ $row['bronze'] }}</td>
                        <td class="px-3 py-2" data-label="Total">{{ $row['total'] }}</td>
                        <td class="px-3 py-2" data-label="Peserta">{{ $row['participants'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @include('partials.pagination', ['paginator' => $rows])
@endsection
