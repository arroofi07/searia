@extends('pdf.layout')

@section('title', 'Buku Acara · '.$competitionName)
@section('document-title', 'Buku Acara')

@section('content')
    @if (!empty($includeToc) && count($document->sessions) > 0)
        <h2 style="font-size:12px;margin:0 0 6px;">Daftar isi</h2>
        <div class="toc">
            @foreach ($document->sessions as $session)
                @foreach ($session->events as $event)
                    <div class="toc-row">
                        Sesi {{ $session->session }} · Acara {{ $event->eventNumber }} — {{ $event->eventName }}
                    </div>
                @endforeach
            @endforeach
        </div>
        <div class="page-break"></div>
    @endif

    @foreach ($document->sessions as $session)
        <h2 style="font-size:13px;margin:0 0 10px;">Sesi {{ $session->session }}</h2>

        @foreach ($session->events as $event)
            <div class="event-block">
                <div class="event-title">{{ $event->title() }}</div>

                @foreach ($event->ageGroups as $ageGroup)
                    <div class="group-title">{{ $ageGroup->name }}</div>

                    @foreach ($ageGroup->heats as $heat)
                        <div class="heat-title">Seri {{ $heat->heatNumber }}</div>
                        <table class="lanes">
                            <thead>
                                <tr>
                                    <th style="width:8%">Lint.</th>
                                    <th>Nama</th>
                                    <th style="width:8%">Thn</th>
                                    <th style="width:8%">KU</th>
                                    <th style="width:18%">Klub</th>
                                    <th style="width:14%">Kota</th>
                                    <th style="width:12%">Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($heat->lanes as $lane)
                                    <tr>
                                        <td>{{ $lane->laneNumber }}</td>
                                        @if ($lane->isEmpty())
                                            <td colspan="6" class="empty">kosong</td>
                                        @else
                                            <td>{{ $lane->athleteName }}</td>
                                            <td>{{ $lane->birthYear }}</td>
                                            <td>{{ $lane->ageGroupCode }}</td>
                                            <td>{{ $lane->clubName }}</td>
                                            <td>{{ $lane->city }}</td>
                                            <td>{{ $lane->formattedSeedTime() }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endforeach
                @endforeach
            </div>
        @endforeach

        @if (! $loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
@endsection
