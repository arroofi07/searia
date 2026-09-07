<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Sertifikat Juara</title>
    <style>
        @page { margin: 12mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            @if($backgroundPath)
            background-image: url('{{ $backgroundPath }}');
            background-size: cover;
            background-position: center;
            @endif
        }
        .frame {
            border: 4px solid #b45309;
            padding: 28px 36px;
            min-height: 480px;
            text-align: center;
        }
        .eyebrow { font-size: 12px; letter-spacing: 2px; text-transform: uppercase; color: #b45309; margin: 0; }
        .title { font-size: 28px; margin: 8px 0 4px; color: #92400e; }
        .subtitle { font-size: 12px; color: #475569; margin: 0 0 18px; }
        .rank {
            font-size: 22px;
            font-weight: bold;
            color: #b45309;
            margin: 8px 0;
        }
        .name {
            font-size: 26px;
            font-weight: bold;
            margin: 12px 0 6px;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            line-height: 1.25;
            max-width: 100%;
        }
        .meta { font-size: 13px; color: #334155; margin: 4px 0; }
        .event { font-size: 14px; margin-top: 16px; font-weight: bold; }
        .time { font-size: 18px; margin-top: 8px; color: #b45309; }
        .footer { margin-top: 36px; width: 100%; }
        .signer { display: inline-block; width: 45%; text-align: center; vertical-align: top; }
        .signer .line { border-top: 1px solid #0f172a; margin: 40px 24px 6px; }
        .qr { display: inline-block; width: 45%; text-align: center; vertical-align: top; }
        .qr img { width: 90px; height: 90px; }
        .code { font-size: 10px; color: #64748b; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="frame">
        <p class="eyebrow">Sertifikat Juara</p>
        <h1 class="title">{{ $competition->name }}</h1>
        <p class="subtitle">{{ $competition->venue }}@if($competition->city), {{ $competition->city }}@endif</p>

        <p class="rank">Peringkat {{ $rank }}@if($ageGroupName) · {{ $ageGroupName }}@endif</p>
        <p class="meta">Diberikan kepada</p>
        <p class="name">{{ $athleteName }}</p>
        <p class="meta">{{ $clubName }}@if($city) · {{ $city }}@endif</p>

        <p class="event">{{ $eventLabel }}</p>
        <p class="time">Catatan waktu: {{ $timeLabel }}</p>

        <div class="footer">
            <div class="signer">
                <div class="line"></div>
                <div><strong>{{ $signerName }}</strong></div>
                <div class="meta">{{ $signerTitle }}</div>
            </div>
            <div class="qr">
                <img src="{{ $qrUrl }}" alt="QR verifikasi">
                <div class="code">Kode: {{ $code }}</div>
            </div>
        </div>
    </div>
</body>
</html>
