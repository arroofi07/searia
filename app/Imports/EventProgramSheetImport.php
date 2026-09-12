<?php

namespace App\Imports;

use App\DataTransferObjects\ParticipantRow;
use App\Services\EventProgram\EventProgramHeaders;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;

class EventProgramSheetImport implements ToCollection
{
    use Importable;

    /** @var list<array{excel_row: int, kode: string, nama: string, gender: string, grup: string}> */
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

            if ($this->dataRows > 200) {
                $this->tooManyRows = true;

                return;
            }

            $this->rows[] = [
                'excel_row' => $this->excelRow,
                'kode' => $this->value($values, EventProgramHeaders::KODE_ACARA),
                'nama' => $this->value($values, EventProgramHeaders::NOMOR_PERLOMBAAN),
                'gender' => $this->value($values, EventProgramHeaders::GENDER),
                'grup' => $this->value($values, EventProgramHeaders::GRUP),
            ];
        }
    }

    /**
     * @param  list<mixed>  $values
     */
    private function parseHeader(array $values): void
    {
        foreach ($values as $index => $value) {
            $normalized = EventProgramHeaders::normalize(ParticipantRow::stringify($value));

            if ($normalized === '') {
                continue;
            }

            $this->indexes[$normalized] = (int) $index;
        }

        $this->missingColumns = array_values(array_filter(
            EventProgramHeaders::required(),
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
