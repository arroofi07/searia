<?php

namespace App\Services\Import;

use App\DataTransferObjects\ParticipantRow;

class ImportValidationResult
{
    /**
     * @param  list<ValidatedImportRow>  $rows
     * @param  list<string>  $missingColumns
     */
    public function __construct(
        public array $rows = [],
        public array $missingColumns = [],
        public ?string $fileError = null,
        public int $read = 0,
        public int $valid = 0,
        public int $invalid = 0,
        public int $warningRows = 0,
    ) {}

    /**
     * @param  list<ValidatedImportRow>  $rows
     */
    public static function fromRows(array $rows): self
    {
        $valid = 0;
        $invalid = 0;
        $warnings = 0;

        foreach ($rows as $row) {
            if ($row->isValid()) {
                $valid++;
            } else {
                $invalid++;
            }

            if ($row->hasWarnings()) {
                $warnings++;
            }
        }

        return new self(
            rows: $rows,
            read: count($rows),
            valid: $valid,
            invalid: $invalid,
            warningRows: $warnings,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rows' => array_map(fn (ValidatedImportRow $row): array => $row->toArray(), $this->rows),
            'missing_columns' => $this->missingColumns,
            'file_error' => $this->fileError,
            'read' => $this->read,
            'valid' => $this->valid,
            'invalid' => $this->invalid,
            'warning_rows' => $this->warningRows,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $rows = array_map(
            fn (array $row): ValidatedImportRow => ValidatedImportRow::fromArray($row),
            $payload['rows'] ?? [],
        );

        return new self(
            rows: $rows,
            missingColumns: $payload['missing_columns'] ?? [],
            fileError: $payload['file_error'] ?? null,
            read: (int) ($payload['read'] ?? count($rows)),
            valid: (int) ($payload['valid'] ?? 0),
            invalid: (int) ($payload['invalid'] ?? 0),
            warningRows: (int) ($payload['warning_rows'] ?? 0),
        );
    }

    /**
     * @return list<ValidatedImportRow>
     */
    public function validRows(): array
    {
        return array_values(array_filter($this->rows, fn (ValidatedImportRow $row): bool => $row->isValid()));
    }

    /**
     * @return list<ValidatedImportRow>
     */
    public function invalidRows(): array
    {
        return array_values(array_filter($this->rows, fn (ValidatedImportRow $row): bool => ! $row->isValid()));
    }

    public function rowByExcelNumber(int $excelRow): ?ValidatedImportRow
    {
        foreach ($this->rows as $row) {
            if ($row->row->excelRow === $excelRow) {
                return $row;
            }
        }

        return null;
    }

    public function replaceRow(ValidatedImportRow $replacement): self
    {
        $rows = [];

        foreach ($this->rows as $row) {
            $rows[] = $row->row->excelRow === $replacement->row->excelRow ? $replacement : $row;
        }

        return self::fromRows($rows);
    }

    /**
     * @return list<ParticipantRow>
     */
    public function participantRows(): array
    {
        return array_map(fn (ValidatedImportRow $row): ParticipantRow => $row->row, $this->rows);
    }
}
