@extends('pdf.layout')

@section('title', 'Club Terbaik · '.$competitionName)
@section('document-title', 'Club Terbaik')

@section('content')
    <h2 style="text-align:center;font-size:13px;letter-spacing:0.6px;margin:0 0 12px;">CLUB TERBAIK</h2>

    <table class="lanes">
        <thead>
            <tr>
                <th style="width:7%">NO</th>
                <th>CLUB</th>
                <th style="width:18%">KOTA</th>
                <th style="width:10%">EMAS</th>
                <th style="width:10%">PERAK</th>
                <th style="width:12%">PERUNGGU</th>
                <th style="width:10%">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['club_name'] }}</td>
                    <td>{{ $row['city'] }}</td>
                    <td>{{ $row['gold'] ?: '' }}</td>
                    <td>{{ $row['silver'] ?: '' }}</td>
                    <td>{{ $row['bronze'] ?: '' }}</td>
                    <td>{{ $row['total'] ?: '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">Belum ada klub pada klasemen ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
