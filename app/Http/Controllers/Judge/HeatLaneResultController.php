<?php

namespace App\Http\Controllers\Judge;

use App\Actions\RecordLaneResult;
use App\Enums\DisqualificationCode;
use App\Enums\ResultStatus;
use App\Exceptions\CannotRecordResultException;
use App\Http\Controllers\Controller;
use App\Models\HeatLane;
use App\Models\Result;
use App\Support\SwimTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HeatLaneResultController extends Controller
{
    public function upsert(Request $request, HeatLane $heatLane, RecordLaneResult $recorder): JsonResponse
    {
        $this->authorize('record', [Result::class, $heatLane]);

        $data = $request->validate([
            'status' => ['required', Rule::enum(ResultStatus::class)],
            'time' => ['nullable', 'string', 'max:32'],
            'dsq_code' => ['nullable', Rule::enum(DisqualificationCode::class)],
            'dsq_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $status = ResultStatus::from($data['status']);

        if ($status === ResultStatus::Dsq && blank($data['dsq_code'] ?? null)) {
            throw ValidationException::withMessages([
                'dsq_code' => 'Kode diskualifikasi wajib diisi.',
            ]);
        }

        if ($status === ResultStatus::Ok && blank($data['time'] ?? null)) {
            throw ValidationException::withMessages([
                'time' => 'Catatan waktu wajib diisi.',
            ]);
        }

        try {
            $result = $recorder->handle($heatLane, $data, $request->user(), allowLocked: false);
        } catch (CannotRecordResultException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $heatLane->load('heat');

        return response()->json([
            'ok' => true,
            'result' => [
                'id' => $result->id,
                'status' => $result->status->value,
                'time_ms' => $result->time_ms,
                'time_formatted' => SwimTime::formatMilliseconds($result->time_ms),
                'dsq_code' => $result->dsq_code?->value,
                'dsq_reason' => $result->dsq_reason,
            ],
            'heat_fully_recorded' => $heatLane->heat?->fresh()?->isFullyRecorded() ?? false,
        ]);
    }
}
