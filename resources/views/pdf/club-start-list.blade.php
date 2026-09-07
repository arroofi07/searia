@extends('pdf.layout')

@section('title', 'Start List Klub · '.$club->name)
@section('document-title', 'Start List Klub · '.$club->name)

@section('content')
    <p style="margin:0 0 10px;font-size:11px;">{{ $club->name }}@if($club->city) · {{ $club->city }}@endif</p>

    <table class="lanes">
        <thead>
            <tr>
                <th style="width:8%">Acara</th>
                <th>Nomor</th>
                <th style="width:14%">KU</th>
                <th style="width:8%">Seri</th>
                <th style="width:8%">Lint.</th>
                <th>Atlet</th>
                <th style="width:12%">Waktu</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr>
                    <td>{{ $entry['event_number'] }}</td>
                    <td>{{ $entry['event_name'] }}</td>
                    <td>{{ $entry['age_group'] }}</td>
                    <td>{{ $entry['heat_number'] }}</td>
                    <td>{{ $entry['lane_number'] }}</td>
                    <td>{{ $entry['athlete_name'] }}</td>
                    <td>{{ $entry['seed_time'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">Tidak ada entri klub pada start list.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
