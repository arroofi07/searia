<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Notifications\ImportValidationCompleted;
use App\Services\Import\ParticipantFileReader;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ValidateImportBatch implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public int $uniqueFor = 600;

    public function __construct(public int $importBatchId) {}

    public function uniqueId(): string
    {
        return 'validate-import-'.$this->importBatchId;
    }

    public function handle(ParticipantFileReader $reader): void
    {
        $batch = ImportBatch::query()->with(['competition', 'uploader'])->findOrFail($this->importBatchId);
        Cache::put($this->progressKey(), 20, 600);

        $path = Storage::disk('local')->path($batch->stored_path);
        $extension = pathinfo($batch->original_filename, PATHINFO_EXTENSION) ?: 'xlsx';

        try {
            $result = $reader->readAndValidate($path, $extension, $batch->competition, $batch->uploader);
            Cache::put($this->progressKey(), 90, 600);
            $batch->storeResult($result);
            $batch->uploader?->notify(new ImportValidationCompleted($batch->fresh()));
            Cache::put($this->progressKey(), 100, 600);
        } catch (Throwable $exception) {
            $batch->markFailed($exception->getMessage());
            Cache::put($this->progressKey(), 100, 600);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $batch = ImportBatch::query()->find($this->importBatchId);
        $batch?->markFailed($exception?->getMessage() ?? 'Validasi gagal.');
    }

    private function progressKey(): string
    {
        return 'import-progress.'.$this->importBatchId;
    }
}
