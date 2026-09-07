<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>@yield('title', $competitionName ?? 'SeaRIA')</title>
    <style>
        @page { margin: 18mm 14mm 18mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; }
        .header { border-bottom: 1.5px solid #0f172a; padding-bottom: 8px; margin-bottom: 12px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; border: none; padding: 0; }
        .header .logo { width: 48px; height: 48px; }
        .header h1 { font-size: 14px; margin: 0 0 2px; }
        .header p { margin: 0; color: #334155; font-size: 9px; }
        .footer { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 8px; color: #64748b; border-top: 0.5px solid #cbd5e1; padding-top: 4px; }
        .footer .page:after { content: counter(page); }
        .toc { margin-top: 8px; }
        .toc-row { padding: 2px 0; border-bottom: 0.3px dotted #94a3b8; }
        .event-block { page-break-inside: avoid; margin-bottom: 14px; }
        .event-title { font-size: 12px; font-weight: bold; margin: 0 0 4px; }
        .group-title { font-size: 10px; font-weight: bold; margin: 8px 0 4px; color: #134e4a; }
        .heat-title { font-size: 10px; margin: 6px 0 2px; }
        table.lanes { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.lanes th, table.lanes td { border: 0.5px solid #94a3b8; padding: 3px 4px; text-align: left; }
        table.lanes th { background: #f1f5f9; font-size: 8px; text-transform: uppercase; }
        .empty { color: #94a3b8; font-style: italic; }
        .signature { margin-top: 18px; width: 45%; }
        .signature .line { border-bottom: 0.5px solid #0f172a; height: 28px; margin-bottom: 4px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    @php
        $logoPath = $logoPath ?? null;
        if ($logoPath === null) {
            $configured = config('searia.pdf.organizer_logo');
            if (is_string($configured) && $configured !== '' && is_file($configured)) {
                $logoPath = 'file://'.str_replace('\\', '/', $configured);
            }
        }
    @endphp
    <div class="header">
        <table class="header-table">
            <tr>
                @if ($logoPath)
                    <td style="width:58px"><img class="logo" src="{{ $logoPath }}" alt="Logo"></td>
                @endif
                <td>
                    <h1>{{ $competitionName ?? '' }}</h1>
                    <p>{{ $venue ?? '' }}@if(!empty($city)), {{ $city }}@endif · {{ $dateLabel ?? '' }}</p>
                    <p>@yield('document-title', 'Buku Acara') · Dicetak: {{ ($printedAt ?? now())->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                </td>
            </tr>
        </table>
    </div>

    @yield('content')

    <div class="footer">
        <span>{{ $competitionName ?? 'SeaRIA' }}</span>
        <span style="float:right">Halaman <span class="page"></span></span>
    </div>
</body>
</html>
