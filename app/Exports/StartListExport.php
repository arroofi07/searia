<?php

namespace App\Exports;

use App\Models\Competition;
use App\Services\StartListBuilder;
use App\Support\SwimTime;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class StartListExport extends StringValueBinder implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithTitle
{
    public function __construct(
        private readonly Competition $competition,
        private readonly bool $blankResults = false,
        private readonly ?string $sheetTitle = null,
        private readonly ?int $eventId = null,
    ) {}

    public function title(): string
    {
        if ($this->sheetTitle !== null) {
            return mb_substr($this->sheetTitle, 0, 31);
        }

        return $this->blankResults ? 'LEMBAR HASIL' : 'START LIST';
    }

    public function headings(): array
    {
        $headings = [
            'KODE ACARA',
            'NOMOR ACARA',
            'KELOMPOK UMUR',
            'SERI',
            'LINTASAN',
            'NAMA LENGKAP',
            'TAHUN LAHIR',
            'KLUB/SEKOLAH',
            'KABUPATEN/KOTA',
            'CATATAN WAKTU',
        ];

        if ($this->blankResults) {
            $headings[] = 'HASIL';
            $headings[] = 'STATUS';
            $headings[] = 'DSQ';
        }

        return $headings;
    }

    public function collection(): Collection
    {
        $document = app(StartListBuilder::class)->build(
            $this->competition,
            eventId: $this->eventId,
        );
        $rows = collect();

        foreach ($document->sessions as $session) {
            foreach ($session->events as $event) {
                foreach ($event->ageGroups as $ageGroup) {
                    foreach ($ageGroup->heats as $heat) {
                        foreach ($heat->lanes as $lane) {
                            if ($lane->isEmpty() && ! $this->blankResults) {
                                continue;
                            }

                            $row = [
                                (string) $event->eventNumber,
                                $event->eventName,
                                $ageGroup->name,
                                (string) $heat->heatNumber,
                                (string) $lane->laneNumber,
                                (string) ($lane->athleteName ?? ''),
                                (string) ($lane->birthYear ?? ''),
                                (string) ($lane->clubName ?? ''),
                                (string) ($lane->city ?? ''),
                                $lane->isEmpty() ? '' : SwimTime::formatMilliseconds($lane->seedTimeMs),
                            ];

                            if ($this->blankResults) {
                                $row[] = '';
                                $row[] = $lane->isEmpty() ? '' : 'OK';
                                $row[] = '';
                            }

                            $rows->push($row);
                        }
                    }
                }
            }
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        $formats = [
            'A' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_TEXT,
            'J' => NumberFormat::FORMAT_TEXT,
        ];

        if ($this->blankResults) {
            $formats['K'] = NumberFormat::FORMAT_TEXT;
            $formats['L'] = NumberFormat::FORMAT_TEXT;
        }

        return $formats;
    }
}
