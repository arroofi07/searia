@extends('pdf.layout')

@section('title', 'Hasil Lomba · '.$competitionName)
@section('document-title', 'Hasil Lomba')

@section('content')
    @foreach ($document->sessions as $session)
        <h2 style="font-size:13px;margin:0 0 10px;">Sesi {{ $session->session }}</h2>

        @foreach ($session->events as $event)
            <div class="event-block">
                <div class="event-title">{{ $event->title() }}</div>

                @foreach ($event->ageGroups as $ageGroup)
                    <div class="group-title">{{ $ageGroup->name }}</div>

                    <table class="lanes">
                        <thead>
                            <tr>
                                <th style="width:8%">TEMPAT</th>
                                <th>NAMA</th>
                                <th style="width:7%">YOB</th>
                                <th style="width:7%">AGE</th>
                                <th style="width:16%">CLUB</th>
                                <th style="width:12%">KAB/KOTA</th>
                                <th style="width:7%">SERI</th>
                                <th style="width:7%">LANE</th>
                                <th style="width:12%">HASIL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ageGroup->lanes as $lane)
                                <tr>
                                    <td>{{ $lane->formattedRank() }}</td>
                                    <td>{{ $lane->athleteName }}</td>
                                    <td>{{ $lane->birthYear }}</td>
                                    <td>{{ $lane->ageGroupCode }}</td>
                                    <td>{{ $lane->clubName }}</td>
                                    <td>{{ $lane->city }}</td>
                                    <td>{{ $lane->heatNumber }}</td>
                                    <td>{{ $lane->laneNumber }}</td>
                                    <td>{{ $lane->formattedResult() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            </div>
        @endforeach

        @if (! $loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
@endsection
