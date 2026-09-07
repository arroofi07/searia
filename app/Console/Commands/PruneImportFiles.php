<?php

namespace App\Console\Commands;

use App\Models\ImportBatch;
use Illuminate\Console\Command;

class PruneImportFiles extends Command
{
    protected $signature = 'imports:prune';

    protected $description = 'Hapus berkas asal import yang lebih tua dari masa retensi';

    public function handle(): int
    {
        $days = (int) config('searia.import.file_retention_days', 90);
        $cutoff = now()->subDays($days);
        $deleted = 0;

        ImportBatch::query()
            ->where('created_at', '<', $cutoff)
            ->where('stored_path', '!=', '')
            ->each(function (ImportBatch $batch) use (&$deleted): void {
                $batch->deleteStoredFile();
                $deleted++;
            });

        $this->info($deleted.' berkas import dihapus.');

        return self::SUCCESS;
    }
}
