<?php

namespace App\Actions;

use App\Enums\DisqualificationCode;
use App\Enums\ResultStatus;
use App\Exceptions\CannotRecordResultException;
use App\Models\ActivityLog;
use App\Models\Result;
use App\Models\User;
use App\Support\SwimTime;
use Illuminate\Support\Facades\DB;

class CorrectResult
{
    /**
     * @param  array{status: string|ResultStatus, time?: string|int|null, time_ms?: int|null, dsq_code?: string|null, dsq_reason?: string|null}  $payload
     */
    public function handle(Result $result, array $payload, User $actor, string $reason, ?string $ipAddress = null, ?bool $fastInput = null): Result
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new CannotRecordResultException('Alasan koreksi wajib diisi.');
        }

        $result->loadMissing(['heatLane.heat.event.competition']);
        $competition = $result->heatLane?->heat?->event?->competition;
        $fastInput ??= $competition?->allowsFastTimeInput() ?? (bool) config('searia.swim_time.fast_input', true);

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

        return DB::transaction(function () use ($result, $status, $timeMs, $dsqCode, $dsqReason, $actor, $reason, $ipAddress): Result {
            $old = $result->snapshot();

            $result->fill([
                'time_ms' => $timeMs,
                'status' => $status,
                'dsq_code' => $dsqCode,
                'dsq_reason' => $status === ResultStatus::Dsq ? $dsqReason : null,
                'recorded_by' => $actor->id,
                'recorded_at' => now(),
                'verified_by' => null,
                'verified_at' => null,
            ]);
            $result->save();

            ActivityLog::query()->create([
                'user_id' => $actor->id,
                'action' => 'result.correct',
                'subject_type' => Result::class,
                'subject_id' => $result->id,
                'old_values' => $old,
                'new_values' => $result->fresh()->snapshot(),
                'reason' => $reason,
                'ip_address' => $ipAddress,
            ]);

            return $result->fresh();
        });
    }
}
