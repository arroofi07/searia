<?php

namespace App\Imports;

use App\DataTransferObjects\ParticipantRow;
use App\Services\Import\ImportHeaders;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ParticipantSheetImport implements ToCollection, WithChunkReading
{
    use Importable;

    /** @var list<ParticipantRow> */
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
        $maxRows = (int) config('searia.import.max_rows', 2000);

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

            if ($this->dataRows > $maxRows) {
                $this->tooManyRows = true;

                return;
            }

            $this->rows[] = new ParticipantRow(
                excelRow: $this->excelRow,
                no: $this->value($values, ImportHeaders::NO),
                fullName: $this->value($values, ImportHeaders::NAMA_LENGKAP),
                gender: $this->value($values, ImportHeaders::GENDER),
                birthYear: $this->value($values, ImportHeaders::TAHUN_LAHIR),
                clubName: $this->value($values, ImportHeaders::KLUB),
                city: $this->value($values, ImportHeaders::KOTA),
                eventCode: $this->value($values, ImportHeaders::KODE_ACARA),
                seedTime: $this->value($values, ImportHeaders::CATATAN_WAKTU),
            );
        }
    }

    public function chunkSize(): int
    {
        return (int) config('searia.import.chunk_size', 500);
    }

    /**
     * @param  list<mixed>  $values
     */
    private function parseHeader(array $values): void
    {
        foreach ($values as $index => $value) {
            $normalized = ImportHeaders::normalize(ParticipantRow::stringify($value));

            if ($normalized === '') {
                continue;
            }

            $this->indexes[$normalized] = (int) $index;
        }

        $this->missingColumns = array_values(array_filter(
            ImportHeaders::required(),
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
