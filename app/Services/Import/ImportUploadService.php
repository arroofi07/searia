<?php

namespace App\Services\Import;

use App\Enums\ImportStatus;
use App\Jobs\ValidateImportBatch;
use App\Models\Competition;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportUploadService
{
    public function __construct(private readonly ParticipantFileReader $reader) {}

    public function store(Competition $competition, User $user, UploadedFile $file): ImportBatch
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'xlsx');
        $stored = $file->storeAs(
            'imports/'.$competition->id,
            Str::uuid()->toString().'.'.$extension,
            'local',
        );

        $batch = ImportBatch::query()->create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $stored,
            'status' => ImportStatus::Uploaded,
        ]);

        $absolute = Storage::disk('local')->path($stored);
        $estimated = $this->estimateDataRows($absolute, $extension);
        $queueAfter = (int) config('searia.import.queue_after_rows', 200);

        if ($estimated > $queueAfter) {
            $batch->update([
                'status' => ImportStatus::Validating,
                'total_rows' => $estimated,
            ]);
            ValidateImportBatch::dispatch($batch->id);

            return $batch->fresh();
        }

        $result = $this->reader->readAndValidate($absolute, $extension, $competition, $user);
        $batch->storeResult($result);

        return $batch->fresh();
    }

    public function estimateDataRows(string $path, string $extension): int
    {
        if (strtolower($extension) === 'csv') {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            return max(0, count($lines ?: []) - 1);
        }

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getSheetByName('PESERTA') ?? $spreadsheet->getSheet(0);
        $highest = (int) $sheet->getHighestDataRow();
        $spreadsheet->disconnectWorksheets();

        return max(0, $highest - 1);
    }
}
