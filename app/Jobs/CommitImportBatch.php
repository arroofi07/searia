<?php

namespace App\Jobs;

use App\Actions\CommitImportBatch as CommitImportBatchAction;
use App\Models\ImportBatch;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class CommitImportBatch implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public int $uniqueFor = 600;

    public function __construct(public int $importBatchId) {}

    public function uniqueId(): string
    {
        return 'commit-import-'.$this->importBatchId;
    }

    public function handle(CommitImportBatchAction $action): void
    {
        $batch = ImportBatch::query()->with('competition')->findOrFail($this->importBatchId);
        $action->handle($batch);
    }

    public function failed(?Throwable $exception): void
    {
        $batch = ImportBatch::query()->find($this->importBatchId);
        $batch?->markFailed($exception?->getMessage() ?? 'Penyimpanan gagal.');
    }
}
