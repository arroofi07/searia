<?php

namespace App\Exports;

use App\Models\Competition;
use App\Services\ClubStanding;
use App\Services\MedalTally;
use App\Services\RankingCalculator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MedalTallyExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private readonly Competition $competition) {}

    public function title(): string
    {
        return 'MEDALI';
    }

    public function headings(): array
    {
        return ['KLUB', 'KOTA', 'EMAS', 'PERAK', 'PERUNGGU', 'TOTAL', 'PESERTA'];
    }

    public function collection(): Collection
    {
        $rows = app(ClubStanding::class)->forCompetition(
            $this->competition,
            app(MedalTally::class),
            app(RankingCalculator::class),
        );

        return $rows->map(fn (array $row) => [
            $row['club_name'],
            (string) ($row['city'] ?? ''),
            $row['gold'],
            $row['silver'],
            $row['bronze'],
            $row['total'],
            $row['participants'],
        ]);
    }
}
