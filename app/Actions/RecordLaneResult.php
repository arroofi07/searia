<?php

namespace App\Actions;

use App\Enums\DisqualificationCode;
use App\Enums\ResultStatus;
use App\Exceptions\CannotRecordResultException;
use App\Models\HeatLane;
use App\Models\Result;
use App\Models\User;
use App\Support\SwimTime;
use Illuminate\Support\Facades\DB;

class RecordLaneResult
{
    /**
     * @param  array{status: string|ResultStatus, time?: string|int|null, time_ms?: int|null, dsq_code?: string|null, dsq_reason?: string|null}  $payload
     */
    public function handle(HeatLane $lane, array $payload, User $actor, bool $allowLocked = false, ?bool $fastInput = null): Result
    {
        $lane->loadMissing(['heat.event.competition', 'registration', 'result']);

        if ($lane->registration_id === null) {
            throw new CannotRecordResultException('Lintasan kosong tidak dapat diisi hasil.');
        }

        $heat = $lane->heat;
        if ($heat === null) {
            throw new CannotRecordResultException('Seri tidak ditemukan.');
        }

        if (! $allowLocked && $heat->isResultsLocked()) {
            throw new CannotRecordResultException('Seri sudah dikunci dan tidak dapat diubah.');
        }

        $status = $payload['status'] instanceof ResultStatus
            ? $payload['status']
            : ResultStatus::from((string) $payload['status']);

        $timeMs = null;
        if ($status->requiresTime()) {
            if (array_key_exists('time_ms', $payload) && $payload['time_ms'] !== null && $payload['time_ms'] !== '') {
                $timeMs = (int) $payload['time_ms'];
            } else {
                $raw = $payload['time'] ?? null;
                if ($raw === null || $raw === '') {
                    throw new CannotRecordResultException('Catatan waktu wajib diisi untuk status OK.');
                }
                $fastInput ??= $heat->event?->competition?->allowsFastTimeInput()
                    ?? (bool) config('searia.swim_time.fast_input', true);
                $timeMs = SwimTime::parse(is_string($raw) ? $raw : (string) $raw, $fastInput)?->milliseconds;
                if ($timeMs === null) {
                    throw new CannotRecordResultException('Catatan waktu wajib diisi untuk status OK.');
                }
            }
        }

        $dsqCode = null;
        $dsqReason = $payload['dsq_reason'] ?? null;
        if ($status->requiresDsqCode()) {
            $code = $payload['dsq_code'] ?? null;
            if ($code === null || $code === '') {
                throw new CannotRecordResultException('Kode diskualifikasi wajib diisi.');
            }
            $dsqCode = $code instanceof DisqualificationCode
                ? $code
                : DisqualificationCode::from((string) $code);
        }

        return DB::transaction(function () use ($lane, $status, $timeMs, $dsqCode, $dsqReason, $actor): Result {
            /** @var Result $result */
            $result = Result::query()->updateOrCreate(
                ['heat_lane_id' => $lane->id],
                [
                    'time_ms' => $timeMs,
                    'status' => $status,
                    'dsq_code' => $dsqCode,
                    'dsq_reason' => $status === ResultStatus::Dsq ? $dsqReason : null,
                    'recorded_by' => $actor->id,
                    'recorded_at' => now(),
                ],
            );

            return $result->fresh();
        });
    }
}
