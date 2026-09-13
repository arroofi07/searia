<?php

namespace App\Imports;

use App\DataTransferObjects\ParticipantRow;
use App\Services\AgeGroupImport\AgeGroupImportHeaders;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;

class AgeGroupSheetImport implements ToCollection
{
    use Importable;

    /** @var list<array{excel_row: int, kode: string, nama: string, label: string, tahun_awal: string, tahun_akhir: string, urutan: string}> */
    public array $rows = [];

    /** @var list<string> */
    public array $missingColumns = [];

    public bool $headerParsed = false;

    public bool $tooManyRows = false;

    public int $excelRow = 0;

    public int $dataRows = 0;

    /** @var array<string, int> */
    private array $indexes = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->excelRow++;
            $values = array_values($row instanceof Collection ? $row->all() : (array) $row);

            if (! $this->headerParsed) {
                $this->parseHeader($values);
                $this->headerParsed = true;

                continue;
            }

            if ($this->missingColumns !== []) {
                continue;
            }

            if ($this->isEmpty($values)) {
                continue;
            }

            $this->dataRows++;

            if ($this->dataRows > 50) {
                $this->tooManyRows = true;

                return;
            }

            $this->rows[] = [
                'excel_row' => $this->excelRow,
                'kode' => $this->value($values, AgeGroupImportHeaders::KODE),
                'nama' => $this->value($values, AgeGroupImportHeaders::NAMA),
                'label' => $this->value($values, AgeGroupImportHeaders::LABEL),
                'tahun_awal' => $this->value($values, AgeGroupImportHeaders::TAHUN_AWAL),
                'tahun_akhir' => $this->value($values, AgeGroupImportHeaders::TAHUN_AKHIR),
                'urutan' => $this->value($values, AgeGroupImportHeaders::URUTAN),
            ];
        }
    }

    /**
     * @param  list<mixed>  $values
     */
    private function parseHeader(array $values): void
    {
        foreach ($values as $index => $value) {
            $normalized = AgeGroupImportHeaders::normalize(ParticipantRow::stringify($value));

            if ($normalized === '') {
                continue;
            }

            $this->indexes[$normalized] = (int) $index;
        }

        $this->missingColumns = array_values(array_filter(
            AgeGroupImportHeaders::required(),
            fn (string $column): bool => ! array_key_exists($column, $this->indexes),
        ));
    }

    /**
     * @param  list<mixed>  $values
     */
    private function value(array $values, string $column): string
    {
        if (! array_key_exists($column, $this->indexes)) {
            return '';
        }

        return ParticipantRow::stringify($values[$this->indexes[$column]] ?? null);
    }

    /**
     * @param  list<mixed>  $values
     */
    private function isEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if (ParticipantRow::stringify($value) !== '') {
                return false;
            }
        }

        return true;
    }
}
