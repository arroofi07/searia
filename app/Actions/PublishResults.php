<?php

namespace App\Actions;

use App\Enums\CompetitionStatus;
use App\Exceptions\CannotTransitionCompetitionException;
use App\Models\Competition;
use App\Models\Heat;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\ResultsPublished;
use App\Services\CompetitionStatusTransition;
use Illuminate\Support\Facades\Notification;

class PublishResults
{
    public function __construct(private readonly CompetitionStatusTransition $transition) {}

    public function handle(Competition $competition, User $actor, ?string $ipAddress = null): Competition
    {
        if ($competition->status !== CompetitionStatus::Finished) {
            throw new CannotTransitionCompetitionException(
                'Publikasi hanya dari status selesai (finished).',
            );
        }

        if ($this->hasUnlockedHeats($competition)) {
            throw new CannotTransitionCompetitionException(
                'Publikasi ditolak karena masih ada seri yang belum dikunci.',
            );
        }

        if ($this->transition->hasUnverifiedResults($competition)) {
            throw new CannotTransitionCompetitionException(
                'Publikasi ditolak karena masih ada hasil yang belum diverifikasi.',
            );
        }

        $published = $this->transition->transition(
            $competition,
            CompetitionStatus::Published,
            $actor,
            reason: null,
            ipAddress: $ipAddress,
        );

        $coachIds = Registration::query()
            ->where('competition_id', $published->id)
            ->whereNotNull('registered_by')
            ->distinct()
            ->pluck('registered_by');

        $coaches = User::query()->whereIn('id', $coachIds)->get();
        Notification::send($coaches, new ResultsPublished($published));

        app(GenerateCertificates::class)->handle($published);

        \App\Support\PublicPageCache::bump();

        return $published->fresh();
    }

    public function hasUnlockedHeats(Competition $competition): bool
    {
        return Heat::query()
            ->whereHas('event', fn ($q) => $q->where('competition_id', $competition->id))
            ->whereHas('lanes', fn ($q) => $q->whereNotNull('registration_id'))
            ->whereNull('results_locked_at')
            ->exists();
    }
}
