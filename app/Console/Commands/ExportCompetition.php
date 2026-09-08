<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportCompetition extends Command
{
    protected $signature = 'competition:export {competition : ID atau slug} {--path= : Path berkas keluaran}';

    protected $description = 'Ekspor satu kejuaraan lengkap ke berkas JSON';

    public function handle(): int
    {
        $key = $this->argument('competition');
        $competition = Competition::query()
            ->where('id', $key)
            ->orWhere('slug', $key)
            ->firstOrFail();

        $payload = $this->buildPayload($competition);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $relative = $this->option('path') ?: 'backups/competition-'.$competition->slug.'-'.now()->format('YmdHis').'.json';
        if (! str_contains($relative, DIRECTORY_SEPARATOR) && ! str_starts_with($relative, 'backups/')) {
            $relative = 'backups/'.$relative;
        }

        Storage::disk('local')->put($relative, $json ?: '{}');
        $this->info('Diekspor ke '.Storage::disk('local')->path($relative));

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(Competition $competition): array
    {
        $competition->load([
            'ageGroups',
            'events.ageGroups',
            'events.judges',
            'events.heats.lanes.result',
            'registrations.athlete.club',
            'invoices',
            'certificates',
        ]);

        $clubs = [];
        $athletes = [];
        foreach ($competition->registrations as $registration) {
            $athlete = $registration->athlete;
            $club = $athlete?->club;
            if ($club !== null) {
                $clubs[$club->id] = $club->only([
                    'id', 'name', 'short_name', 'type', 'city', 'province', 'logo_path', 'status', 'is_active',
                ]);
            }
            if ($athlete !== null) {
                $athletes[$athlete->id] = $athlete->only([
                    'id', 'club_id', 'full_name', 'gender', 'birth_year', 'birth_date', 'identity_number', 'photo_path',
                ]);
            }
        }

        $heats = [];
        $lanes = [];
        $results = [];
        foreach ($competition->events as $event) {
            foreach ($event->heats as $heat) {
                $heats[] = $heat->only([
                    'id', 'event_id', 'age_group_id', 'heat_number', 'round', 'locked_at', 'results_locked_at',
                ]);
                foreach ($heat->lanes as $lane) {
                    $lanes[] = $lane->only(['id', 'heat_id', 'lane_number', 'registration_id']);
                    if ($lane->result !== null) {
                        $results[] = $lane->result->only([
                            'id', 'heat_lane_id', 'time_ms', 'status', 'dsq_code', 'dsq_reason',
                            'recorded_by', 'recorded_at', 'verified_by', 'verified_at',
                        ]);
                    }
                }
            }
        }

        return [
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'competition' => $competition->attributesToArray(),
            'age_groups' => $competition->ageGroups->map->attributesToArray()->all(),
            'events' => $competition->events->map(fn (Event $event) => [
                ...$event->attributesToArray(),
                'age_group_ids' => $event->ageGroups->pluck('id')->all(),
                'judge_ids' => $event->judges->pluck('id')->all(),
            ])->all(),
            'clubs' => array_values($clubs),
            'athletes' => array_values($athletes),
            'registrations' => $competition->registrations->map->attributesToArray()->all(),
            'invoices' => $competition->invoices->map->attributesToArray()->all(),
            'heats' => $heats,
            'heat_lanes' => $lanes,
            'results' => $results,
            'certificates' => $competition->certificates->map->attributesToArray()->all(),
            'counts' => [
                'age_groups' => $competition->ageGroups->count(),
                'events' => $competition->events->count(),
                'registrations' => $competition->registrations->count(),
                'heats' => count($heats),
                'heat_lanes' => count($lanes),
                'results' => count($results),
                'certificates' => $competition->certificates->count(),
                'invoices' => $competition->invoices->count(),
            ],
        ];
    }
}
