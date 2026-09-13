<?php

namespace App\Exports\Sheets;

use App\Models\AgeGroup;
use App\Models\Competition;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class KelompokUmurSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private readonly Competition $competition) {}

    public function title(): string
    {
        return 'KELOMPOK UMUR';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['KODE', 'NAMA', 'LABEL CETAK', 'TAHUN LAHIR AWAL', 'TAHUN LAHIR AKHIR', 'URUTAN'];
    }

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        $groups = $this->competition->ageGroups;

        if ($groups->isEmpty()) {
            return collect(AgeGroup::defaultDefinitions($this->competition->year()))
                ->map(fn (array $definition): array => $this->row($definition))
                ->values()
                ->all();
        }

        return $groups
            ->sortBy('sort_order')
            ->map(fn (AgeGroup $group): array => $this->row([
                'code' => $group->code,
                'name' => $group->name,
                'display_code' => $group->display_code ?? '',
                'birth_year_start' => $group->birth_year_start,
                'birth_year_end' => $group->birth_year_end,
                'sort_order' => $group->sort_order,
            ]))
            ->values()
            ->all();
    }

    /**
     * @param  array{code: string, name: string, display_code: string, birth_year_start: int, birth_year_end: int, sort_order: int}  $definition
     * @return list<string>
     */
    private function row(array $definition): array
    {
        return [
            (string) $definition['code'],
            $definition['name'],
            (string) $definition['display_code'],
            (string) $definition['birth_year_start'],
            (string) $definition['birth_year_end'],
            (string) $definition['sort_order'],
        ];
    }
}
