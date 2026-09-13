<?php

namespace App\Actions;

use App\DataTransferObjects\AgeGroupImportRow;
use App\Exceptions\MissingImportColumnsException;
use App\Imports\AgeGroupSheetImport;
use App\Imports\AgeGroupWorkbookImport;
use App\Models\AgeGroup;
use App\Models\Competition;
use App\Services\AgeGroupImport\AgeGroupImportParser;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportAgeGroups
{
    public function __construct(private readonly AgeGroupImportParser $parser) {}

    /**
     * @return array{created: int, updated: int, errors: list<string>}
     */
    public function handle(Competition $competition, string $path, string $extension): array
    {
        $sheet = $this->readSheet($path, $extension);

        if ($sheet->missingColumns !== []) {
            throw new MissingImportColumnsException($sheet->missingColumns);
        }

        if ($sheet->tooManyRows) {
            return $this->emptyResult(['Berkas melebihi 50 baris.']);
        }

        if ($sheet->rows === []) {
            return $this->emptyResult(['Tidak ada baris kelompok umur yang bisa dibaca.']);
        }

        $competition->load(['ageGroups.registrations']);
        $parsed = $this->parseRows($sheet->rows, $competition);

        if ($parsed['errors'] !== []) {
            return $this->emptyResult($parsed['errors']);
        }

        return $this->commit($competition, $parsed['rows']);
    }

    /**
     * @param  list<array{excel_row: int, kode: string, nama: string, label: string, tahun_awal: string, tahun_akhir: string, urutan: string}>  $rows
     * @return array{rows: list<AgeGroupImportRow>, errors: list<string>}
     */
    private function parseRows(array $rows, Competition $competition): array
    {
        $errors = [];
        $parsed = [];
        $seen = [];
        $currentYear = (int) now()->year;
        $existing = $competition->ageGroups->keyBy('code');

        foreach ($rows as $index => $row) {
            $line = 'Baris '.$row['excel_row'].': ';
            $code = $this->parser->parseCode($row['kode']);

            if ($code === null) {
                $errors[] = $line.'KODE wajib diisi, maksimal 10 karakter.';

                continue;
            }

            $codeKey = mb_strtolower($code);

            if (isset($seen[$codeKey])) {
                $errors[] = $line.'KODE '.$code.' muncul lebih dari sekali di berkas.';

                continue;
            }

            $seen[$codeKey] = true;
            $name = $this->parser->parseName($row['nama']);

            if ($name === null) {
                $errors[] = $line.'NAMA wajib diisi, maksimal 50 karakter.';

                continue;
            }

            $rawLabel = trim($row['label']);
            $display = $this->parser->parseDisplayCode($rawLabel);

            if ($rawLabel !== '' && $display === null) {
                $errors[] = $line.'LABEL CETAK maksimal 10 karakter.';

                continue;
            }

            $start = $this->parser->parseYear($row['tahun_awal']);
            $end = $this->parser->parseYear($row['tahun_akhir']);

            if ($start === null || $end === null) {
                $errors[] = $line.'TAHUN LAHIR AWAL dan AKHIR harus tahun 4 digit.';

                continue;
            }

            if ($start < 1950 || $start > $currentYear) {
                $errors[] = $line.'TAHUN LAHIR AWAL harus antara 1950 dan '.$currentYear.'.';

                continue;
            }

            if ($end < 1950 || $end > ($currentYear + 1)) {
                $errors[] = $line.'TAHUN LAHIR AKHIR harus antara 1950 dan '.($currentYear + 1).'.';

                continue;
            }

            if ($start > $end) {
                $errors[] = $line.'Tahun lahir awal tidak boleh lebih besar daripada tahun lahir akhir.';

                continue;
            }

            $rawSort = trim($row['urutan']);
            $sort = $this->parser->parseSort($rawSort);

            if ($rawSort !== '' && $sort === null) {
                $errors[] = $line.'URUTAN harus angka 1–99.';

                continue;
            }

            /** @var AgeGroup|null $group */
            $group = $existing->get($code);

            if ($sort === null) {
                $sort = $group?->sort_order ?? ($index + 1);
            }

            if ($group instanceof AgeGroup && $group->registrations->isNotEmpty()) {
                if ($group->birth_year_start !== $start || $group->birth_year_end !== $end) {
                    $errors[] = $line.$group->name.' sudah punya pendaftaran, tahun lahir tidak boleh diubah.';

                    continue;
                }
            }

            $parsed[] = new AgeGroupImportRow(
                excelRow: $row['excel_row'],
                code: $code,
                name: $name,
                displayCode: $display ?? $group?->display_code,
                birthYearStart: $start,
                birthYearEnd: $end,
                sortOrder: $sort,
            );
        }

        if ($errors === []) {
            $errors = $this->overlapErrors($competition, $parsed);
        }

        return [
            'rows' => $parsed,
            'errors' => $errors,
        ];
    }

    /**
     * @param  list<AgeGroupImportRow>  $rows
     * @return list<string>
     */
    private function overlapErrors(Competition $competition, array $rows): array
    {
        $imported = [];

        foreach ($rows as $row) {
            $imported[mb_strtolower($row->code)] = true;
        }

        $final = [];

        foreach ($competition->ageGroups as $group) {
            if (isset($imported[mb_strtolower($group->code)])) {
                continue;
            }

            $final[] = [
                'row' => null,
                'name' => $group->name,
                'start' => $group->birth_year_start,
                'end' => $group->birth_year_end,
            ];
        }

        foreach ($rows as $row) {
            $final[] = [
                'row' => $row->excelRow,
                'name' => $row->name,
                'start' => $row->birthYearStart,
                'end' => $row->birthYearEnd,
            ];
        }

        $errors = [];
        $count = count($final);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $left = $final[$i];
                $right = $final[$j];

                if (max($left['start'], $right['start']) > min($left['end'], $right['end'])) {
                    continue;
                }

                $where = $left['row'] === null
                    ? $left['name']
                    : 'Baris '.$left['row'];

                $errors[] = $where.': rentang tahun lahir tumpang tindih dengan '.$right['name'].' ('.$right['start'].'–'.$right['end'].').';
            }
        }

        return $errors;
    }

    /**
     * @param  list<AgeGroupImportRow>  $rows
     * @return array{created: int, updated: int, errors: list<string>}
     */
    private function commit(Competition $competition, array $rows): array
    {
        $created = 0;
        $updated = 0;
        $existing = $competition->ageGroups->keyBy('code');

        DB::transaction(function () use ($competition, $rows, $existing, &$created, &$updated): void {
            foreach ($rows as $row) {
                $attributes = [
                    'name' => $row->name,
                    'display_code' => $row->displayCode,
                    'birth_year_start' => $row->birthYearStart,
                    'birth_year_end' => $row->birthYearEnd,
                    'sort_order' => $row->sortOrder,
                ];

                /** @var AgeGroup|null $group */
                $group = $existing->get($row->code);

                if ($group instanceof AgeGroup) {
                    $group->fill($attributes);
                    $group->save();
                    $updated++;

                    continue;
                }

                $competition->ageGroups()->create([
                    'code' => $row->code,
                    ...$attributes,
                ]);
                $created++;
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'errors' => [],
        ];
    }

    /**
     * @param  list<string>  $errors
     * @return array{created: int, updated: int, errors: list<string>}
     */
    private function emptyResult(array $errors): array
    {
        return [
            'created' => 0,
            'updated' => 0,
            'errors' => $errors,
        ];
    }

    private function readSheet(string $path, string $extension): AgeGroupSheetImport
    {
        $sheet = new AgeGroupSheetImport;
        $readerType = strtolower($extension) === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX;

        if (strtolower($extension) === 'csv') {
            Excel::import($sheet, $path, null, $readerType);

            return $sheet;
        }

        try {
            Excel::import(new AgeGroupWorkbookImport($sheet), $path, null, $readerType);
        } catch (Throwable) {
            $sheet = new AgeGroupSheetImport;
            Excel::import($sheet, $path, null, $readerType);
        }

        return $sheet;
    }
}
