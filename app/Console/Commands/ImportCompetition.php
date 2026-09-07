<?php

namespace App\Console\Commands;

use App\Models\Athlete;
use App\Models\Certificate;
use App\Models\Club;
use App\Models\Competition;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Result;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportCompetition extends Command
{
    protected $signature = 'competition:import {path : Path relatif di disk local atau absolut}';

    protected $description = 'Pulihkan kejuaraan dari berkas JSON hasil competition:export';

    public function handle(): int
    {
        $path = (string) $this->argument('path');
        $absolute = is_file($path) ? $path : Storage::disk('local')->path($path);
        if (! is_file($absolute)) {
            $this->error('Berkas tidak ditemukan: '.$path);

            return self::FAILURE;
        }

        $payload = json_decode((string) file_get_contents($absolute), true);
        if (! is_array($payload) || ($payload['version'] ?? null) !== 1) {
            $this->error('Format berkas tidak dikenali.');

            return self::FAILURE;
        }

        $counts = DB::transaction(fn () => $this->importPayload($payload));

        $this->info('Pemulihan selesai.');
        foreach ($counts as $table => $count) {
            $this->line("  {$table}: {$count}");
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, int>
     */
    private function importPayload(array $payload): array
    {
        $compData = $payload['competition'];
        unset($compData['id']);
        $compData['slug'] = Competition::uniqueSlugFromName(($compData['name'] ?? 'kejuaraan').' restore '.Str::random(4));
        $competition = Competition::query()->create($compData);

        $ageMap = [];
        foreach ($payload['age_groups'] as $row) {
            $oldId = $row['id'];
            unset($row['id'], $row['competition_id']);
            $age = $competition->ageGroups()->create($row);
            $ageMap[$oldId] = $age->id;
        }

        $clubMap = [];
        foreach ($payload['clubs'] as $row) {
            $oldId = $row['id'];
            $club = Club::query()->firstOrCreate(
                ['name' => $row['name']],
                collect($row)->except(['id'])->all(),
            );
            $clubMap[$oldId] = $club->id;
        }

        $athleteMap = [];
        foreach ($payload['athletes'] as $row) {
            $oldId = $row['id'];
            $clubId = $clubMap[$row['club_id']] ?? null;
            if ($clubId === null) {
                continue;
            }
            $attributes = collect($row)->except(['id', 'club_id'])->all();
            $attributes['club_id'] = $clubId;
            $athlete = Athlete::query()->firstOrCreate(
                [
                    'club_id' => $clubId,
                    'full_name' => $row['full_name'],
                    'birth_year' => $row['birth_year'],
                ],
                $attributes,
            );
            $athleteMap[$oldId] = $athlete->id;
        }

        $eventMap = [];
        foreach ($payload['events'] as $row) {
            $oldId = $row['id'];
            $ageGroupIds = $row['age_group_ids'] ?? [];
            unset($row['id'], $row['competition_id'], $row['age_group_ids'], $row['judge_ids']);
            $event = $competition->events()->create($row);
            $event->ageGroups()->sync(collect($ageGroupIds)->map(fn ($id) => $ageMap[$id] ?? null)->filter()->all());
            $eventMap[$oldId] = $event->id;
        }

        $invoiceMap = [];
        foreach ($payload['invoices'] as $row) {
            $oldId = $row['id'];
            unset($row['id'], $row['competition_id']);
            $row['club_id'] = $clubMap[$row['club_id']] ?? $row['club_id'];
            $row['invoice_number'] = ($row['invoice_number'] ?? 'INV').'-R'.Str::upper(Str::random(4));
            $invoice = $competition->invoices()->create($row);
            $invoiceMap[$oldId] = $invoice->id;
        }

        $regMap = [];
        foreach ($payload['registrations'] as $row) {
            $oldId = $row['id'];
            unset($row['id'], $row['competition_id'], $row['import_batch_id']);
            $row['event_id'] = $eventMap[$row['event_id']] ?? null;
            $row['athlete_id'] = $athleteMap[$row['athlete_id']] ?? null;
            $row['age_group_id'] = $ageMap[$row['age_group_id']] ?? null;
            $row['invoice_id'] = isset($row['invoice_id']) ? ($invoiceMap[$row['invoice_id']] ?? null) : null;
            if ($row['event_id'] === null || $row['athlete_id'] === null) {
                continue;
            }
            $registration = $competition->registrations()->create($row);
            $regMap[$oldId] = $registration->id;
        }

        $heatMap = [];
        foreach ($payload['heats'] as $row) {
            $oldId = $row['id'];
            unset($row['id']);
            $row['event_id'] = $eventMap[$row['event_id']] ?? null;
            $row['age_group_id'] = $ageMap[$row['age_group_id']] ?? null;
            if ($row['event_id'] === null) {
                continue;
            }
            $heat = Heat::query()->create($row);
            $heatMap[$oldId] = $heat->id;
        }

        $laneMap = [];
        foreach ($payload['heat_lanes'] as $row) {
            $oldId = $row['id'];
            unset($row['id']);
            $row['heat_id'] = $heatMap[$row['heat_id']] ?? null;
            $row['registration_id'] = isset($row['registration_id']) ? ($regMap[$row['registration_id']] ?? null) : null;
            if ($row['heat_id'] === null) {
                continue;
            }
            $lane = HeatLane::query()->create($row);
            $laneMap[$oldId] = $lane->id;
        }

        $resultCount = 0;
        foreach ($payload['results'] as $row) {
            unset($row['id']);
            $row['heat_lane_id'] = $laneMap[$row['heat_lane_id']] ?? null;
            if ($row['heat_lane_id'] === null) {
                continue;
            }
            Result::query()->create($row);
            $resultCount++;
        }

        $certCount = 0;
        foreach ($payload['certificates'] ?? [] as $row) {
            unset($row['id']);
            $row['competition_id'] = $competition->id;
            $row['athlete_id'] = $athleteMap[$row['athlete_id']] ?? null;
            $row['event_id'] = $eventMap[$row['event_id']] ?? null;
            $row['age_group_id'] = isset($row['age_group_id']) ? ($ageMap[$row['age_group_id']] ?? null) : null;
            $row['result_id'] = null;
            $row['code'] = strtoupper(Str::random(12));
            if ($row['athlete_id'] === null || $row['event_id'] === null) {
                continue;
            }
            Certificate::query()->create($row);
            $certCount++;
        }

        return [
            'competition' => 1,
            'age_groups' => count($ageMap),
            'events' => count($eventMap),
            'registrations' => count($regMap),
            'heats' => count($heatMap),
            'heat_lanes' => count($laneMap),
            'results' => $resultCount,
            'certificates' => $certCount,
            'invoices' => count($invoiceMap),
        ];
    }
}
