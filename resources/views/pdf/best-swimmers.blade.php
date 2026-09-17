@extends('pdf.layout')

@section('title', 'Atlet Terbaik · '.$competitionName)
@section('document-title', 'Daftar Atlet Terbaik')

@section('content')
    <h2 style="text-align:center;font-size:13px;letter-spacing:0.6px;margin:0 0 12px;">DAFTAR ATLET TERBAIK</h2>

    <table class="lanes">
        <thead>
            <tr>
                <th style="width:6%">Pos</th>
                <th style="width:8%">ID</th>
                <th>Nama Atlet</th>
                <th style="width:8%">Sex</th>
                <th style="width:10%">KU</th>
                <th style="width:22%">Nama Tim</th>
                <th style="width:7%">Emas</th>
                <th style="width:7%">Perak</th>
                <th style="width:9%">Perunggu</th>
                <th style="width:7%">Poin</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groups as $group)
                @foreach ($group['winners'] as $winner)
                    <tr>
                        <td>{{ $winner['position'] }}</td>
                        <td>{{ $winner['identity_number'] ?: $winner['athlete_id'] }}</td>
                        <td>{{ $winner['athlete_name'] }}</td>
                        <td>{{ strtoupper($winner['gender_label']) }}</td>
                        <td>{{ $winner['age_group_name'] }}</td>
                        <td>
                            {{ $winner['club_name'] }}
                            @if ($winner['city'])
                                <br>{{ $winner['city'] }}
                            @endif
                        </td>
                        <td>{{ $winner['gold'] ?: 0 }}</td>
                        <td>{{ $winner['silver'] ?: 0 }}</td>
                        <td>{{ $winner['bronze'] ?: 0 }}</td>
                        <td>{{ $winner['points'] }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="10" class="empty">Belum ada atlet terbaik. Medali belum dihitung.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
