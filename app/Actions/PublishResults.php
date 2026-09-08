<?php

namespace App\Actions;

use App\Enums\CompetitionStatus;
use App\Exceptions\CannotTransitionCompetitionException;
use App\Models\Competition;
use App\Models\Heat;
use App\Models\RegistrationSubmission;
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

        $this->notifyRegistrants($published);

        app(GenerateCertificates::class)->handle($published);

        \App\Support\PublicPageCache::bump();

        return $published->fresh();
    }

    /**
     * Pendaftar tidak punya akun, jadi kabar hasil terbit dikirim ke alamat email
     * yang dicantumkan pada form pendaftaran. Satu email per pengiriman.
     */
    private function notifyRegistrants(Competition $competition): void
    {
        RegistrationSubmission::query()
            ->where('competition_id', $competition->id)
            ->whereNotNull('registrant_email')
            ->pluck('registrant_email')
            ->unique()
            ->each(fn (string $email) => Notification::route('mail', $email)
                ->notify(new ResultsPublished($competition)));
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
