<?php

namespace App\Imports;

use App\Actions\RecordLaneResult;
use App\Enums\DisqualificationCode;
use App\Enums\ResultStatus;
use App\Exceptions\CannotRecordResultException;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\User;
use App\Support\SwimTime;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BlankResultSheetImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;

    public function __construct(
        private readonly Competition $competition,
        private readonly User $actor,
    ) {}

    public function collection(Collection $rows): void
    {
        $recorder = app(RecordLaneResult::class);

        foreach ($rows as $row) {
            $eventNumber = (int) ($row['kode_acara'] ?? $row['kode acara'] ?? 0);
            $ageGroupName = trim((string) ($row['kelompok_umur'] ?? $row['kelompok umur'] ?? ''));
            $heatNumber = (int) ($row['seri'] ?? 0);
            $laneNumber = (int) ($row['lintasan'] ?? 0);
            $time = trim((string) ($row['hasil'] ?? ''));
            $statusRaw = strtoupper(trim((string) ($row['status'] ?? '')));
            $dsq = strtoupper(trim((string) ($row['dsq'] ?? '')));

            if ($eventNumber === 0 || $heatNumber === 0 || $laneNumber === 0) {
                continue;
            }

            if ($statusRaw === '' && $time === '') {
                continue;
            }

            $status = match ($statusRaw) {
                'OK', '' => ResultStatus::Ok,
                'DNS' => ResultStatus::Dns,
                'DNF' => ResultStatus::Dnf,
                'DSQ' => ResultStatus::Dsq,
                default => null,
            };

            if ($status === null) {
                continue;
            }

            $event = Event::query()
                ->where('competition_id', $this->competition->id)
                ->where('event_number', $eventNumber)
                ->first();

            if ($event === null) {
                continue;
            }

            $heatQuery = Heat::query()
                ->where('event_id', $event->id)
                ->where('heat_number', $heatNumber);

            if ($ageGroupName !== '') {
                $heatQuery->whereHas('ageGroup', function ($query) use ($ageGroupName): void {
                    $query->where('name', $ageGroupName)
                        ->orWhere('code', $ageGroupName)
                        ->orWhere('display_code', $ageGroupName);
                });
            }

            $heat = $heatQuery->first();
            if ($heat === null) {
                continue;
            }

            $lane = HeatLane::query()
                ->where('heat_id', $heat->id)
                ->where('lane_number', $laneNumber)
                ->whereNotNull('registration_id')
                ->first();

            if ($lane === null) {
                continue;
            }

            $payload = [
                'status' => $status,
                'time' => $status === ResultStatus::Ok ? ($time !== '' ? $time : null) : null,
                'dsq_code' => null,
            ];

            if ($status === ResultStatus::Dsq) {
                $code = DisqualificationCode::tryFrom($dsq !== '' ? $dsq : 'OT');
                if ($code === null) {
                    continue;
                }
                $payload['dsq_code'] = $code;
            }

            if ($status === ResultStatus::Ok && ($payload['time'] === null || SwimTime::parse((string) $payload['time']) === null)) {
                continue;
            }

            try {
                $recorder->handle($lane, $payload, $this->actor, allowLocked: true);
                $this->imported++;
            } catch (CannotRecordResultException) {
                continue;
            }
        }
    }
}
