@extends('pdf.layout')

@section('title', 'Lembar Hasil · '.$competitionName)
@section('document-title', 'Lembar Hasil Kosong')

@section('content')
    @php $heatIndex = 0; $heatTotal = 0; @endphp
    @foreach ($document->sessions as $session)
        @foreach ($session->events as $event)
            @foreach ($event->ageGroups as $ageGroup)
                @foreach ($ageGroup->heats as $heat)
                    @php $heatTotal++; @endphp
                @endforeach
            @endforeach
        @endforeach
    @endforeach

    @foreach ($document->sessions as $session)
        @foreach ($session->events as $event)
            @foreach ($event->ageGroups as $ageGroup)
                @foreach ($ageGroup->heats as $heat)
                    @php $heatIndex++; @endphp
                    <div class="event-block">
                        <div class="event-title">{{ $event->title() }}</div>
                        <div class="group-title">{{ $ageGroup->name }} · Seri {{ $heat->heatNumber }}</div>

                        <table class="lanes">
                            <thead>
                                <tr>
                                    <th style="width:7%">Lint.</th>
                                    <th>Nama</th>
                                    <th style="width:7%">Thn</th>
                                    <th style="width:7%">KU</th>
                                    <th style="width:16%">Klub</th>
                                    <th style="width:18%">Waktu</th>
                                    <th style="width:12%">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($heat->lanes as $lane)
                                    <tr>
                                        <td>{{ $lane->laneNumber }}</td>
                                        @if ($lane->isEmpty())
                                            <td colspan="4" class="empty">kosong</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                        @else
                                            <td>{{ $lane->athleteName }}</td>
                                            <td>{{ $lane->birthYear }}</td>
                                            <td>{{ $lane->ageGroupCode }}</td>
                                            <td>{{ $lane->clubName }}</td>
                                            <td style="height:22px;">&nbsp;</td>
                                            <td>&nbsp;</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="signature">
                            <div class="line"></div>
                            <div>Tanda tangan juri</div>
                        </div>
                    </div>

                    @if ($heatIndex < $heatTotal)
                        <div class="page-break"></div>
                    @endif
                @endforeach
            @endforeach
        @endforeach
    @endforeach
@endsection
