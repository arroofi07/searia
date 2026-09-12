<?php

namespace App\Exports\Sheets;

use App\Models\Competition;
use App\Models\Event;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class NomorLombaSheet implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithTitle
{
    public function __construct(
        private readonly Competition $competition,
        private readonly bool $protect = true,
    ) {}

    public function title(): string
    {
        return 'NOMOR LOMBA';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['KODE ACARA', 'NOMOR PERLOMBAAN', 'GENDER', 'GRUP YANG BOLEH IKUT'];
    }

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return $this->competition->events
            ->map(function (Event $event): array {
                $groups = $event->ageGroups
                    ->sortBy('sort_order')
                    ->pluck('name')
                    ->implode(', ');

                return [
                    (string) $event->event_number,
                    $event->shortName(),
                    $event->gender->label(),
                    $groups,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                if (! $this->protect) {
                    return;
                }

                $sheet = $event->sheet->getDelegate();
                $note = $sheet->getComment('A1');
                $note->getText()->createTextRun(
                    'Lembar rujukan terkunci. Salin KODE ACARA ke lembar PESERTA. Unggah peserta lewat Pendaftaran → Import Excel. Susunan nomor dan grup diubah di halaman Nomor lomba.'
                );
                $note->setWidth('280px');
                $note->setHeight('90px');

                $protection = $sheet->getProtection();
                $protection->setPassword('searia');
                $protection->setSheet(true);
            },
        ];
    }
}
