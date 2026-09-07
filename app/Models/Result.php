<?php

namespace App\Models;

use App\Casts\SwimTimeCast;
use App\Concerns\LogsActivity;
use App\Enums\DisqualificationCode;
use App\Enums\ResultStatus;
use App\Exceptions\InvalidResultException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    /** @use HasFactory<\Database\Factories\ResultFactory> */
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'heat_lane_id',
        'time_ms',
        'status',
        'dsq_code',
        'dsq_reason',
        'recorded_by',
        'recorded_at',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'time_ms' => SwimTimeCast::class,
            'status' => ResultStatus::class,
            'dsq_code' => DisqualificationCode::class,
            'recorded_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Result $result): void {
            $result->assertTimeMatchesStatus();
        });
    }

    public function assertTimeMatchesStatus(): void
    {
        $status = $this->status instanceof ResultStatus
            ? $this->status
            : ResultStatus::from((string) $this->status);

        if ($status->requiresTime() && $this->time_ms === null) {
            throw new InvalidResultException('Status OK wajib disertai catatan waktu.');
        }

        if (! $status->requiresTime() && $this->time_ms !== null) {
            throw new InvalidResultException('Status '.$status->label().' tidak boleh disertai catatan waktu.');
        }

        if ($status->requiresDsqCode() && $this->dsq_code === null) {
            throw new InvalidResultException('Status DSQ wajib disertai kode diskualifikasi.');
        }

        if (! $status->requiresDsqCode()) {
            $this->dsq_code = null;
            if ($status !== ResultStatus::Dsq) {
                $this->dsq_reason = null;
            }
        }
    }

    /**
     * @return BelongsTo<HeatLane, $this>
     */
    public function heatLane(): BelongsTo
    {
        return $this->belongsTo(HeatLane::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * @return array{time_ms: int|null, status: string, dsq_code: string|null, dsq_reason: string|null}
     */
    public function snapshot(): array
    {
        return [
            'time_ms' => $this->time_ms,
            'status' => $this->status instanceof ResultStatus ? $this->status->value : (string) $this->status,
            'dsq_code' => $this->dsq_code instanceof DisqualificationCode ? $this->dsq_code->value : $this->dsq_code,
            'dsq_reason' => $this->dsq_reason,
        ];
    }
}
