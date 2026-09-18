<?php

namespace App\Models;

use App\Enums\CompetitionStatus;
use App\Enums\CompetitionType;
use App\Enums\SeedingMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Competition extends Model
{
    /** @use HasFactory<\Database\Factories\CompetitionFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'venue',
        'city',
        'start_date',
        'end_date',
        'registration_opens_at',
        'registration_closes_at',
        'technical_meeting_at',
        'type',
        'pool_lanes',
        'pool_length',
        'max_events_per_athlete',
        'seeding_mode',
        'fee_per_event',
        'late_fee_per_event',
        'fast_time_input',
        'status',
        'published_at',
        'banner_path',
        'certificate_background_path',
        'certificate_signer_name',
        'certificate_signer_title',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'technical_meeting_at' => 'datetime',
            'type' => CompetitionType::class,
            'pool_lanes' => 'integer',
            'pool_length' => 'integer',
            'max_events_per_athlete' => 'integer',
            'seeding_mode' => SeedingMode::class,
            'fee_per_event' => 'integer',
            'late_fee_per_event' => 'integer',
            'fast_time_input' => 'boolean',
            'status' => CompetitionStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Competition $competition): void {
            if (blank($competition->slug)) {
                $competition->slug = static::uniqueSlugFromName($competition->name);
            }
        });
    }

    public static function uniqueSlugFromName(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'kejuaraan';
        }

        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  Builder<Competition>  $query
     * @return Builder<Competition>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', CompetitionStatus::Published);
    }

    /**
     * @return HasMany<AgeGroup, $this>
     */
    public function ageGroups(): HasMany
    {
        return $this->hasMany(AgeGroup::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class)
            ->orderBy('session')
            ->orderBy('sort_order')
            ->orderBy('event_number');
    }

    /**
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * @return HasMany<ImportBatch, $this>
     */
    public function importBatches(): HasMany
    {
        return $this->hasMany(ImportBatch::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<Certificate, $this>
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * @return HasMany<CertificateArchive, $this>
     */
    public function certificateArchives(): HasMany
    {
        return $this->hasMany(CertificateArchive::class);
    }

    public function isDraft(): bool
    {
        return $this->status === CompetitionStatus::Draft;
    }

    public function isOpenForRegistration(): bool
    {
        return $this->status === CompetitionStatus::Registration;
    }

    public function allowsCommitteeRegistration(): bool
    {
        return in_array($this->status, [CompetitionStatus::Registration, CompetitionStatus::Closed], true);
    }

    public function year(): int
    {
        return (int) $this->start_date->year;
    }

    public function allowsFastTimeInput(): bool
    {
        if ($this->fast_time_input === null) {
            return (bool) config('searia.swim_time.fast_input', true);
        }

        return (bool) $this->fast_time_input;
    }

    /**
     * Nomor yang masih punya peserta disetujui yang belum masuk lintasan.
     * Nomor atau grup tanpa peserta tidak ikut dihitung.
     *
     * @return Collection<int, Event>
     */
    public function eventsPendingSeeding(): Collection
    {
        $events = $this->events()
            ->with([
                'heats:id,event_id,age_group_id,locked_at',
                'heats.lanes:id,heat_id,registration_id',
                'registrations' => fn ($query) => $query->eligibleForSeeding()->select('id', 'event_id', 'age_group_id'),
            ])
            ->get();

        return $events
            ->filter(function (Event $event): bool {
                return $this->unseededAgeGroupIds($event)->isNotEmpty();
            })
            ->values();
    }

    /**
     * Nomor × kelompok umur yang masih punya peserta disetujui di luar lintasan.
     *
     * @return Collection<int, array{event_id: int, age_group_id: int, event_number: string, event_name: string, age_group_name: string|null, label: string, missing_count: int, locked: bool}>
     */
    public function pendingSeedingItems(?Event $only = null): Collection
    {
        $events = $this->eventsPendingSeeding();
        if ($only instanceof Event) {
            $events = $events->where('id', $only->id)->values();
        }

        $groupIds = $events
            ->flatMap(fn (Event $event) => $this->unseededAgeGroupIds($event))
            ->unique()
            ->all();

        $groups = AgeGroup::query()
            ->whereIn('id', $groupIds)
            ->get()
            ->keyBy('id');

        return $events
            ->flatMap(function (Event $event) use ($groups): Collection {
                return $this->unseededAgeGroupIds($event)
                    ->map(function (int $groupId) use ($event, $groups): array {
                        $group = $groups->get($groupId);
                        $groupName = $group instanceof AgeGroup ? $group->name : null;
                        $label = $event->paddedEventNumber().' '.$event->formattedName();
                        if ($groupName !== null) {
                            $label .= ' · '.$groupName;
                        }

                        $groupHeats = $event->heats->where('age_group_id', $groupId);
                        $assignedIds = $groupHeats
                            ->flatMap(fn (Heat $heat) => $heat->lanes->pluck('registration_id'))
                            ->filter();

                        return [
                            'event_id' => $event->id,
                            'age_group_id' => $groupId,
                            'event_number' => $event->paddedEventNumber(),
                            'event_name' => $event->formattedName(),
                            'age_group_name' => $groupName,
                            'label' => $label,
                            'missing_count' => $event->registrations
                                ->where('age_group_id', $groupId)
                                ->reject(fn (Registration $registration): bool => $assignedIds->contains($registration->id))
                                ->count(),
                            'locked' => $groupHeats->isNotEmpty()
                                && $groupHeats->every(fn (Heat $heat): bool => $heat->isLocked()),
                        ];
                    });
            })
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    public function pendingSeedingLabels(?Event $only = null): Collection
    {
        return $this->pendingSeedingItems($only)->pluck('label')->values();
    }

    /**
     * @return Collection<int, int>
     */
    private function unseededAgeGroupIds(Event $event): Collection
    {
        $eligible = $event->registrations;
        if ($eligible->isEmpty()) {
            return collect();
        }

        $assignedIds = $event->heats
            ->loadMissing('lanes')
            ->flatMap(fn (Heat $heat) => $heat->lanes->pluck('registration_id'))
            ->filter()
            ->unique();

        return $eligible
            ->reject(fn (Registration $registration): bool => $assignedIds->contains($registration->id))
            ->pluck('age_group_id')
            ->unique()
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->values();
    }

    public function hasPublicStartList(): bool
    {
        return $this->status->isSeededOrLater();
    }

    public function hasPublicResults(): bool
    {
        return $this->status === CompetitionStatus::Published;
    }

    /**
     * @param  Builder<Competition>  $query
     * @return Builder<Competition>
     */
    public function scopeOpenRegistration(Builder $query): Builder
    {
        return $query->where('status', CompetitionStatus::Registration);
    }

    /**
     * Kejuaraan yang sudah punya seri, belum masuk arsip hasil.
     *
     * @param  Builder<Competition>  $query
     * @return Builder<Competition>
     */
    public function scopeLiveMeet(Builder $query): Builder
    {
        return $query->whereIn('status', [
            CompetitionStatus::Seeded,
            CompetitionStatus::Running,
            CompetitionStatus::Finished,
        ]);
    }

    /**
     * @param  Builder<Competition>  $query
     * @return Builder<Competition>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CompetitionStatus::Published);
    }

    /**
     * Visible on public schedule/fees (never draft).
     *
     * @param  Builder<Competition>  $query
     * @return Builder<Competition>
     */
    public function scopePublicInfo(Builder $query): Builder
    {
        return $query->where('status', '!=', CompetitionStatus::Draft);
    }
}
