<?php

namespace App\Actions;

use App\DataTransferObjects\EventProgramRow;
use App\Exceptions\MissingImportColumnsException;
use App\Imports\EventProgramSheetImport;
use App\Imports\EventProgramWorkbookImport;
use App\Models\AgeGroup;
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
     * @return array{created: int, updated: int, groups_created: int, errors: list<string>}
     */
    public function handle(Competition $competition, string $path, string $extension): array
    {
        $sheet = $this->readSheet($path, $extension);

        if ($sheet->missingColumns !== []) {
            throw new MissingImportColumnsException($sheet->missingColumns);
        }

        if ($sheet->tooManyRows) {
            return $this->emptyResult(['Berkas melebihi 200 baris.']);
        }

        if ($sheet->rows === []) {
            return $this->emptyResult(['Tidak ada baris nomor lomba yang bisa dibaca.']);
        }

        $competition->load(['ageGroups', 'events.registrations']);
        $parsed = $this->parseRows($sheet->rows, $competition->ageGroups);

        if ($parsed['rows'] === []) {
            return $this->emptyResult(
                $parsed['errors'] !== []
                    ? $parsed['errors']
                    : ['Tidak ada baris nomor lomba yang bisa dibaca.'],
            );
        }

        $result = $this->commit($competition, $parsed['rows'], $parsed['group_names']);
        $result['errors'] = array_values(array_merge($parsed['errors'], $result['errors']));

        return $result;
    }

    /**
     * @param  list<array{excel_row: int, kode: string, nama: string, gender: string, grup: string}>  $rows
     * @param  Collection<int, AgeGroup>  $ageGroups
     * @return array{rows: list<EventProgramRow>, errors: list<string>, group_names: array<string, string>}
     */
    private function parseRows(array $rows, Collection $ageGroups): array
    {
        $errors = [];
        $parsed = [];
        $seen = [];
        $neededGroups = [];

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

            $tokens = $this->parser->groupTokens($row['grup']);
            $unknown = [];

            foreach ($tokens as $token) {
                $existing = $this->parser->parseGroups($token, $ageGroups);

                if ($existing['ids'] !== null && $existing['ids'] !== []) {
                    continue;
                }

                $code = $this->parser->matchDefaultGroupCode($token);

                if ($code === null) {
                    $unknown[] = $token;

                    continue;
                }

                $this->rememberGroupName($neededGroups, $code, $token);
            }

            if ($unknown !== []) {
                $errors[] = $line.'Grup tidak dikenali: '.implode(', ', $unknown).'. Pakai Group 1–9, nama seperti Searia1 (angka 1–9), atau nama grup yang sudah ada.';

                continue;
            }

            $parsed[] = new EventProgramRow(
                excelRow: $row['excel_row'],
                eventNumber: $number,
                gender: $gender,
                distance: $name['distance'],
                stroke: $name['stroke'],
                equipment: $name['equipment'],
                groupTokens: $tokens,
                syncGroups: $tokens !== [],
            );
        }

        return [
            'rows' => $parsed,
            'errors' => $errors,
            'group_names' => $neededGroups,
        ];
    }

    /**
     * @param  list<EventProgramRow>  $rows
     * @param  array<string, string>  $groupNames
     * @return array{created: int, updated: int, groups_created: int, errors: list<string>}
     */
    private function commit(Competition $competition, array $rows, array $groupNames): array
    {
        $created = 0;
        $updated = 0;
        $groupsCreated = 0;
        $skipped = [];
        $existing = $competition->events->keyBy('event_number');
        $maxSort = (int) $competition->events->max('sort_order');

        DB::transaction(function () use ($competition, $rows, $groupNames, $existing, &$created, &$updated, &$groupsCreated, &$skipped, &$maxSort): void {
            $groupsCreated = $this->ensureDefaultGroups($competition, $groupNames);
            $ageGroups = $competition->ageGroups()->get();

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

                if ($row->syncGroups) {
                    $resolved = $this->parser->parseGroups(implode(', ', $row->groupTokens), $ageGroups);
                    $event->ageGroups()->sync($resolved['ids'] ?? []);
                }
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'groups_created' => $groupsCreated,
            'errors' => $skipped,
        ];
    }

    /**
     * @param  array<string, string>  $namesByCode
     */
    private function rememberGroupName(array &$namesByCode, string $code, string $token): void
    {
        $custom = ! $this->parser->isDefaultGroupAlias($token);
        $name = $custom ? mb_substr(trim($token), 0, 50) : null;

        if (! isset($namesByCode[$code])) {
            $namesByCode[$code] = $name ?? $this->defaultGroupName($code);

            return;
        }

        if ($custom && $this->parser->isDefaultGroupAlias($namesByCode[$code])) {
            $namesByCode[$code] = $name ?? $namesByCode[$code];
        }
    }

    private function defaultGroupName(string $code): string
    {
        foreach (AgeGroup::defaultDefinitions(2000) as $definition) {
            if ($definition['code'] === $code) {
                return $definition['name'];
            }
        }

        return 'Group '.$code;
    }

    /**
     * @param  array<string, string>  $namesByCode
     */
    private function ensureDefaultGroups(Competition $competition, array $namesByCode): int
    {
        if ($namesByCode === []) {
            return 0;
        }

        $existing = $competition->ageGroups()->pluck('code')->all();
        $defaults = collect(AgeGroup::defaultDefinitions($competition->year()))->keyBy('code');
        $created = 0;

        foreach ($namesByCode as $code => $name) {
            $code = (string) $code;

            if (in_array($code, $existing, true) || ! $defaults->has($code)) {
                continue;
            }

            $definition = $defaults->get($code);
            $definition['name'] = $name !== '' ? $name : $definition['name'];
            $competition->ageGroups()->create($definition);
            $created++;
        }

        $competition->unsetRelation('ageGroups');

        return $created;
    }

    /**
     * @param  list<string>  $errors
     * @return array{created: int, updated: int, groups_created: int, errors: list<string>}
     */
    private function emptyResult(array $errors): array
    {
        return [
            'created' => 0,
            'updated' => 0,
            'groups_created' => 0,
            'errors' => $errors,
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
