<?php

namespace App\Services\Import;

use App\Exceptions\ImportLimitExceededException;
use App\Exceptions\MissingImportColumnsException;
use App\Imports\ParticipantImport;
use App\Imports\ParticipantSheetImport;
use App\Models\Competition;
use App\Models\User;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ParticipantFileReader
{
    public function __construct(
        private readonly RowValidator $validator,
    ) {}

    public function readAndValidate(string $path, string $extension, Competition $competition, ?User $actor = null): ImportValidationResult
    {
        $this->raiseMemoryLimits();

        $sheet = $this->importSheet($path, $extension);

        if ($sheet->missingColumns !== []) {
            throw new MissingImportColumnsException($sheet->missingColumns);
        }

        if ($sheet->tooManyRows) {
            throw new ImportLimitExceededException(
                'Berkas melebihi '.config('searia.import.max_rows').' baris.',
            );
        }

        return $this->validator->validateMany($competition, $sheet->rows, $actor);
    }

    private function importSheet(string $path, string $extension): ParticipantSheetImport
    {
        $sheet = new ParticipantSheetImport;
        $readerType = strtolower($extension) === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX;

        if (strtolower($extension) === 'csv') {
            Excel::import($sheet, $path, null, $readerType);

            return $sheet;
        }

        try {
            Excel::import(new ParticipantImport($sheet), $path, null, $readerType);
        } catch (Throwable) {
            $sheet = new ParticipantSheetImport;
            Excel::import($sheet, $path, null, $readerType);
        }

        return $sheet;
    }

    private function raiseMemoryLimits(): void
    {
        ini_set('memory_limit', (string) config('searia.import.memory_limit', '512M'));
        set_time_limit((int) config('searia.import.time_limit', 120));
    }
}
