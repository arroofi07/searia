@extends('layouts.public')

@section('title', 'Club terbaik · '.$competition->name)
@section('meta_description', 'Club terbaik '.$competition->name.' berdasarkan medali emas, perak, dan perunggu')
@section('og_title', 'Club terbaik · '.$competition->name)

@section('content')
    <div class="mast-row">
        <div>
            @include('public._mast', [
                'competition' => $competition,
                'kicker' => 'Penghargaan',
                'title' => 'Club terbaik',
                'meta' => $competition->name.' · diurutkan emas, perak, perunggu',
            ])
        </div>
        <div class="toolbar">
            @include('public._books', ['competition' => $competition, 'primary' => 'best-club'])
            <a href="{{ route('results.best-club.pdf', $competition) }}" class="public-btn-secondary">Unduh PDF</a>
        </div>
    </div>

    @if ($preview)
        <div class="note">Pratinjau panitia — belum dipublikasikan.</div>
    @endif

    <div class="program">
        <table class="stack-table min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-3 py-2">#</th>
                    <th class="px-3 py-2">Klub</th>
                    <th class="px-3 py-2">Kota</th>
                    <th class="px-3 py-2">Emas</th>
                    <th class="px-3 py-2">Perak</th>
                    <th class="px-3 py-2">Perunggu</th>
                    <th class="px-3 py-2">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $index => $row)
                    <tr class="border-t border-slate-100 {{ $row['has_medals'] ? '' : 'text-slate-500' }}">
                        <td class="px-3 py-2" data-label="Peringkat">{{ $rows->firstItem() + $index }}</td>
                        <td class="px-3 py-2" data-label="Klub">{{ $row['club_name'] }}</td>
                        <td class="px-3 py-2" data-label="Kota">{{ $row['city'] ?? '—' }}</td>
                        <td class="px-3 py-2" data-label="Emas">{{ $row['gold'] ?: '—' }}</td>
                        <td class="px-3 py-2" data-label="Perak">{{ $row['silver'] ?: '—' }}</td>
                        <td class="px-3 py-2" data-label="Perunggu">{{ $row['bronze'] ?: '—' }}</td>
                        <td class="px-3 py-2" data-label="Total">{{ $row['total'] ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-6 text-slate-500">Belum ada klub pada klasemen ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.pagination', ['paginator' => $rows])
@endsection
