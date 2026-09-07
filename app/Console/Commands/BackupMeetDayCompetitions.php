<?php

namespace App\Console\Commands;

use App\Models\Competition;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class BackupMeetDayCompetitions extends Command
{
    protected $signature = 'competition:backup-meet-day';

    protected $description = 'Cadangkan kejuaraan yang akan/ baru selesai dilombakan (sebelum & sesudah hari lomba)';

    public function handle(): int
    {
        $targets = Competition::query()
            ->where(function ($query): void {
                $query->whereDate('start_date', today()->addDay())
                    ->orWhereDate('start_date', today())
                    ->orWhereDate('end_date', today())
                    ->orWhereDate('end_date', today()->subDay());
            })
            ->get();

        foreach ($targets as $competition) {
            Artisan::call('competition:export', [
                'competition' => $competition->id,
            ]);
            $this->line(trim(Artisan::output()));
        }

        $this->info($targets->count().' kejuaraan dicadangkan.');

        return self::SUCCESS;
    }
}
