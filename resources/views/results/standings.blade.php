@extends('layouts.public')

@section('title', 'Klasemen klub · '.$competition->name)
@section('meta_description', 'Klasemen medali klub '.$competition->name)

@section('content')
    <p class="text-sm"><a href="{{ route('results.index', $competition) }}" class="text-teal-800 hover:underline">← Hasil</a></p>
    <h1 class="mt-2 text-2xl font-semibold">Klasemen klub</h1>
    <p class="text-sm text-slate-500">{{ $competition->name }} · diurutkan emas, perak, perunggu</p>
    @if ($preview)
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">Pratinjau panitia</div>
    @endif

    <div class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
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
                        <td class="px-3 py-2">{{ $index + 1 }}</td>
                        <td class="px-3 py-2">
                            {{ $row['club_name'] }}
                            @if ($row['city'])
                                <span class="text-xs text-slate-400">· {{ $row['city'] }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $row['gold'] }}</td>
                        <td class="px-3 py-2">{{ $row['silver'] }}</td>
                        <td class="px-3 py-2">{{ $row['bronze'] }}</td>
                        <td class="px-3 py-2">{{ $row['total'] }}</td>
                        <td class="px-3 py-2">{{ $row['participants'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
