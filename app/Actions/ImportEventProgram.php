<?php

namespace App\Actions;

use App\DataTransferObjects\EventProgramRow;
use App\Exceptions\MissingImportColumnsException;
use App\Imports\EventProgramSheetImport;
use App\Imports\EventProgramWorkbookImport;
use App\Models\Competition;
use App\Models\Event;
use App\Services\EventProgram\EventProgramParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportEventProgram
{
    public function __construct(private readonly EventProgramParser $parser) {}

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
            return [
                'created' => 0,
                'updated' => 0,
                'errors' => ['Berkas melebihi 200 baris.'],
            ];
        }

        if ($sheet->rows === []) {
            return [
                'created' => 0,
                'updated' => 0,
                'errors' => ['Tidak ada baris nomor lomba yang bisa dibaca.'],
            ];
        }

        $competition->load(['ageGroups', 'events.registrations']);
        $parsed = $this->parseRows($sheet->rows, $competition->ageGroups);

        if ($parsed['errors'] !== []) {
            return [
                'created' => 0,
                'updated' => 0,
                'errors' => $parsed['errors'],
            ];
        }

        return $this->commit($competition, $parsed['rows']);
    }

    /**
     * @param  list<array{excel_row: int, kode: string, nama: string, gender: string, grup: string}>  $rows
     * @param  Collection<int, \App\Models\AgeGroup>  $ageGroups
     * @return array{rows: list<EventProgramRow>, errors: list<string>}
     */
    private function parseRows(array $rows, Collection $ageGroups): array
    {
        $errors = [];
        $parsed = [];
        $seen = [];

        foreach ($rows as $row) {
            $line = 'Baris '.$row['excel_row'].': ';
            $number = $this->parser->parseEventNumber($row['kode']);

            if ($number === null) {
                $errors[] = $line.'KODE ACARA harus angka 1–9999.';

                continue;
            }

            if (isset($seen[$number])) {
                $errors[] = $line.'KODE ACARA '.$number.' muncul lebih dari sekali di berkas.';

                continue;
            }

            $seen[$number] = true;
            $gender = $this->parser->parseGender($row['gender']);

            if ($gender === null) {
                $errors[] = $line.'GENDER harus Putra atau Putri.';

                continue;
            }

            $name = $this->parser->parseName($row['nama']);

            if ($name === null) {
                $errors[] = $line.'NOMOR PERLOMBAAN tidak dikenali. Contoh: 50 M Gaya Dada atau 50 M Gaya Kupu-Kupu (Fins).';

                continue;
            }

            $groups = $this->parser->parseGroups($row['grup'], $ageGroups);

            if ($groups['unknown'] !== []) {
                $errors[] = $line.'Grup tidak ditemukan: '.implode(', ', $groups['unknown']).'. Isi kelompok umur dulu, atau pakai nama yang sama persis.';

                continue;
            }

            $parsed[] = new EventProgramRow(
                excelRow: $row['excel_row'],
                eventNumber: $number,
                gender: $gender,
                distance: $name['distance'],
                stroke: $name['stroke'],
                equipment: $name['equipment'],
                ageGroupIds: $groups['ids'],
            );
        }

        return ['rows' => $parsed, 'errors' => $errors];
    }

    /**
     * @param  list<EventProgramRow>  $rows
     * @return array{created: int, updated: int, errors: list<string>}
     */
    private function commit(Competition $competition, array $rows): array
    {
        $created = 0;
        $updated = 0;
        $skipped = [];
        $existing = $competition->events->keyBy('event_number');
        $maxSort = (int) $competition->events->max('sort_order');

        DB::transaction(function () use ($competition, $rows, $existing, &$created, &$updated, &$skipped, &$maxSort): void {
            foreach ($rows as $row) {
                /** @var Event|null $event */
                $event = $existing->get($row->eventNumber);

                if ($event instanceof Event && $event->registrations->isNotEmpty()) {
                    $skipped[] = 'Baris '.$row->excelRow.': nomor '.$row->eventNumber.' sudah punya pendaftaran, dilewati.';

                    continue;
                }

                if ($event instanceof Event) {
                    $event->fill([
                        'gender' => $row->gender,
                        'distance' => $row->distance,
                        'stroke' => $row->stroke,
                        'equipment' => $row->equipment,
                        'is_active' => true,
                    ]);
                    $event->save();
                    $updated++;
                } else {
                    $maxSort++;
                    $event = $competition->events()->create([
                        'event_number' => $row->eventNumber,
                        'gender' => $row->gender,
                        'distance' => $row->distance,
                        'stroke' => $row->stroke,
                        'equipment' => $row->equipment,
                        'session' => 1,
                        'sort_order' => $maxSort,
                        'is_active' => true,
                    ]);
                    $existing->put($row->eventNumber, $event);
                    $created++;
                }

                if ($row->ageGroupIds !== null) {
                    $event->ageGroups()->sync($row->ageGroupIds);
                }
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'errors' => $skipped,
        ];
    }

    private function readSheet(string $path, string $extension): EventProgramSheetImport
    {
        $sheet = new EventProgramSheetImport;
        $readerType = strtolower($extension) === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX;

        if (strtolower($extension) === 'csv') {
            Excel::import($sheet, $path, null, $readerType);

            return $sheet;
        }

        try {
            Excel::import(new EventProgramWorkbookImport($sheet), $path, null, $readerType);
        } catch (Throwable) {
            $sheet = new EventProgramSheetImport;
            Excel::import($sheet, $path, null, $readerType);
        }

        return $sheet;
    }
}
