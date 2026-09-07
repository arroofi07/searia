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
    public function __construct(private readonly Competition $competition) {}

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
                $protection = $event->sheet->getDelegate()->getProtection();
                $protection->setPassword('searia');
                $protection->setSheet(true);
            },
        ];
    }
}
