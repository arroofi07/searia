<?php

namespace App\Models;

use App\Casts\SwimTimeCast;
use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Registration extends Model
{
    /** @use HasFactory<\Database\Factories\RegistrationFactory> */
    use HasFactory;

    protected $fillable = [
        'competition_id',
        'event_id',
        'athlete_id',
        'submission_id',
        'age_group_id',
        'seed_time_ms',
        'status',
        'rejection_reason',
        'registered_by',
        'import_batch_id',
        'invoice_id',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'seed_time_ms' => SwimTimeCast::class,
            'status' => RegistrationStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Registration>  $query
     * @return Builder<Registration>
     */
    public function scopeEligibleForSeeding(Builder $query): Builder
    {
        return $query->where('status', RegistrationStatus::Verified);
    }

    /**
     * @return BelongsTo<Competition, $this>
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<AgeGroup, $this>
     */
    public function ageGroup(): BelongsTo
    {
        return $this->belongsTo(AgeGroup::class);
    }

    /**
     * @return BelongsTo<Athlete, $this>
     */
    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    /**
     * @return BelongsTo<RegistrationSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(RegistrationSubmission::class, 'submission_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<ImportBatch, $this>
     */
    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    /**
     * @return HasOne<HeatLane, $this>
     */
    public function heatLane(): HasOne
    {
        return $this->hasOne(HeatLane::class);
    }

    public function isEditableByEntrant(): bool
    {
        return $this->competition?->status === \App\Enums\CompetitionStatus::Registration
            && in_array($this->status, [RegistrationStatus::Pending, RegistrationStatus::Rejected, RegistrationStatus::Draft], true);
    }
}
