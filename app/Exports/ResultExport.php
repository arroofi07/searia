<?php

namespace App\Exports;

use App\Models\AgeGroup;
use App\Models\Competition;
use App\Models\Event;
use App\Services\RankingCalculator;
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

class ResultExport extends StringValueBinder implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithTitle
{
    public function __construct(
        private readonly Competition $competition,
        private readonly ?Event $event = null,
        private readonly ?string $sheetTitle = null,
    ) {}

    public function title(): string
    {
        if ($this->sheetTitle !== null) {
            return mb_substr($this->sheetTitle, 0, 31);
        }

        return 'HASIL';
    }

    public function headings(): array
    {
        return [
            'KODE ACARA',
            'NOMOR ACARA',
            'KELOMPOK UMUR',
            'PERINGKAT',
            'NAMA LENGKAP',
            'KLUB/SEKOLAH',
            'KABUPATEN/KOTA',
            'HASIL',
            'STATUS',
            'SEED',
            'SELISIH SEED',
            'SERI',
            'LINTASAN',
        ];
    }

    public function collection(): Collection
    {
        $ranking = app(RankingCalculator::class);
        $rows = collect();
        $events = $this->event !== null
            ? collect([$this->event])
            : $this->competition->events()->orderBy('event_number')->get();

        foreach ($events as $event) {
            $ageGroupIds = $event->heats()->distinct()->pluck('age_group_id');
            foreach ($ageGroupIds as $ageGroupId) {
                $ageGroup = AgeGroup::query()->find($ageGroupId);
                if ($ageGroup === null) {
                    continue;
                }

                $table = $ranking->forEventAgeGroup($event, $ageGroup);
                foreach ($table->entries as $entry) {
                    $delta = null;
                    if ($entry->timeMs !== null && $entry->seedTimeMs !== null) {
                        $delta = $entry->timeMs - $entry->seedTimeMs;
                    }

                    $rows->push([
                        (string) $event->event_number,
                        $event->formattedName(),
                        $ageGroup->name,
                        $entry->rank !== null ? (string) $entry->rank : '',
                        $entry->athleteName,
                        $entry->clubName,
                        (string) ($entry->city ?? ''),
                        SwimTime::formatMilliseconds($entry->timeMs),
                        $entry->status->label(),
                        SwimTime::formatMilliseconds($entry->seedTimeMs),
                        $delta === null ? '' : ($delta >= 0 ? '+' : '-').SwimTime::formatMilliseconds(abs($delta)),
                        (string) $entry->heatNumber,
                        (string) $entry->laneNumber,
                    ]);
                }
            }
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_TEXT,
            'J' => NumberFormat::FORMAT_TEXT,
            'K' => NumberFormat::FORMAT_TEXT,
            'L' => NumberFormat::FORMAT_TEXT,
            'M' => NumberFormat::FORMAT_TEXT,
        ];
    }
}
