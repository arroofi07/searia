<?php

namespace App\Services;

use App\Enums\ResultStatus;
use App\Models\Heat;
use App\Models\Result;
use App\Rules\ReasonableSwimTime;
use Illuminate\Support\Collection;

class ResultAnomalyDetector
{
    public const SEED_IMPROVEMENT_THRESHOLD_MS = 10_000;

    /**
     * @return Collection<int, array{type: string, message: string, result_id?: int, heat_id?: int}>
     */
    public function forHeat(Heat $heat): Collection
    {
        $heat->loadMissing([
            'event',
            'lanes.result',
            'lanes.registration',
        ]);

        $anomalies = collect();
        $results = $heat->lanes
            ->map(fn ($lane) => $lane->result)
            ->filter()
            ->values();

        if ($results->isNotEmpty() && $results->every(fn (Result $result): bool => $result->status === ResultStatus::Dns)) {
            $anomalies->push([
                'type' => 'all_dns',
                'message' => 'Seluruh peserta seri berstatus DNS.',
                'heat_id' => $heat->id,
            ]);
        }

        $distance = (int) ($heat->event?->distance ?? 50);
        $bounds = new ReasonableSwimTime($distance, rejectOutOfRange: false);

        foreach ($heat->lanes as $lane) {
            $result = $lane->result;
            if ($result === null || $result->status !== ResultStatus::Ok || $result->time_ms === null) {
                continue;
            }

            $seed = $lane->registration?->seed_time_ms;
            if ($seed !== null && ($seed - $result->time_ms) >= self::SEED_IMPROVEMENT_THRESHOLD_MS) {
                $anomalies->push([
                    'type' => 'fast_vs_seed',
                    'message' => 'Hasil jauh lebih cepat daripada seed time (≥ 10 detik).',
                    'result_id' => $result->id,
                    'heat_id' => $heat->id,
                ]);
            }

            if ($bounds->isOutOfRange($result->time_ms)) {
                $anomalies->push([
                    'type' => 'out_of_bounds',
                    'message' => 'Waktu di luar batas kewajaran untuk jarak '.$distance.' m.',
                    'result_id' => $result->id,
                    'heat_id' => $heat->id,
                ]);
            }
        }

        return $anomalies;
    }
}
