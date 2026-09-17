@extends('pdf.layout')

@section('title', 'Hasil Lomba · '.$competitionName)
@section('document-title', 'Hasil Lomba')

@section('content')
    @foreach ($document->sessions as $session)
        <h2 style="font-size:13px;margin:0 0 10px;">Sesi {{ $session->session }}</h2>

        @foreach ($session->events as $event)
            <div class="event-block" style="page-break-inside:auto;">
                <div class="event-title">{{ $event->title() }}</div>

                <table class="lanes">
                    <thead>
                        <tr>
                            <th style="width:7%">TEMPAT</th>
                            <th>NAMA</th>
                            <th style="width:7%">Umur</th>
                            <th style="width:8%">Group</th>
                            <th style="width:16%">CLUB</th>
                            <th style="width:13%">KAB/KOTA</th>
                            <th style="width:12%">HASIL</th>
                            <th style="width:6%;text-align:center">Emas</th>
                            <th style="width:6%;text-align:center">Perak</th>
                            <th style="width:8%;text-align:center">Perunggu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($event->lanes as $lane)
                            <tr>
                                <td>{{ $lane->formattedRank() }}</td>
                                <td>{{ $lane->athleteName }}</td>
                                <td>{{ $lane->formattedAge() }}</td>
                                <td>{{ $lane->ageGroupCode }}</td>
                                <td>{{ $lane->clubName }}</td>
                                <td>{{ $lane->city }}</td>
                                <td>{{ $lane->formattedResult() }}</td>
                                <td style="text-align:center">@include('partials.medal-icon', ['metal' => $lane->medalMark(1), 'pdf' => true])</td>
                                <td style="text-align:center">@include('partials.medal-icon', ['metal' => $lane->medalMark(2), 'pdf' => true])</td>
                                <td style="text-align:center">@include('partials.medal-icon', ['metal' => $lane->medalMark(3), 'pdf' => true])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        @if (! $loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
@endsection
