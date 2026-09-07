<?php

namespace App\Services;

use App\Enums\CompetitionStatus;
use App\Exceptions\CannotTransitionCompetitionException;
use App\Models\ActivityLog;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\Result;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompetitionStatusTransition
{
    public function canTransition(CompetitionStatus $from, CompetitionStatus $to): bool
    {
        if ($from === $to) {
            return false;
        }

        return $from->canMoveTo($to);
    }

    public function isBackward(CompetitionStatus $from, CompetitionStatus $to): bool
    {
        return $from->isBackward($to);
    }

    public function transition(
        Competition $competition,
        CompetitionStatus $to,
        User $actor,
        ?string $reason = null,
        ?string $ipAddress = null,
    ): Competition {
        $from = $competition->status;

        if (! $this->canTransition($from, $to)) {
            throw new CannotTransitionCompetitionException(
                "Perpindahan status dari {$from->label()} ke {$to->label()} tidak diizinkan.",
            );
        }

        $backward = $this->isBackward($from, $to);

        if ($backward && ! $actor->isSuperAdmin()) {
            throw new CannotTransitionCompetitionException(
                'Hanya Super Admin yang dapat memundurkan status kejuaraan.',
            );
        }

        if ($backward && blank($reason)) {
            throw new CannotTransitionCompetitionException(
                'Perpindahan mundur wajib disertai alasan.',
            );
        }

        if ($to === CompetitionStatus::Seeded) {
            $unseeded = $this->unseededEvents($competition);

            if ($unseeded->isNotEmpty()) {
                throw new CannotTransitionCompetitionException(
                    'Perpindahan ke seeded ditolak karena masih ada nomor lomba yang belum diseeding.',
                );
            }
        }

        if ($to === CompetitionStatus::Published && $this->hasUnverifiedResults($competition)) {
            throw new CannotTransitionCompetitionException(
                'Perpindahan ke published ditolak karena masih ada hasil yang belum diverifikasi.',
            );
        }

        if ($to === CompetitionStatus::Published && $this->hasUnlockedResultHeats($competition)) {
            throw new CannotTransitionCompetitionException(
                'Perpindahan ke published ditolak karena masih ada seri yang belum dikunci.',
            );
        }

        return DB::transaction(function () use ($competition, $from, $to, $actor, $reason, $ipAddress): Competition {
            $payload = ['status' => $to];
            if ($to === CompetitionStatus::Published) {
                $payload['published_at'] = now();
            }
            if ($from === CompetitionStatus::Published && $to !== CompetitionStatus::Published) {
                $payload['published_at'] = null;
            }

            $competition->update($payload);

            ActivityLog::query()->create([
                'user_id' => $actor->id,
                'action' => 'competition.status_change',
                'subject_type' => Competition::class,
                'subject_id' => $competition->id,
                'old_values' => ['status' => $from->value],
                'new_values' => ['status' => $to->value],
                'reason' => $reason,
                'ip_address' => $ipAddress,
            ]);

            return $competition->refresh();
        });
    }

    /**
     * @return Collection<int, Event>
     */
    public function unseededEvents(Competition $competition): Collection
    {
        return $competition->events()
            ->whereDoesntHave('heats')
            ->get();
    }

    public function hasUnverifiedResults(Competition $competition): bool
    {
        return Result::query()
            ->whereNull('verified_at')
            ->whereHas('heatLane.heat.event', function ($query) use ($competition): void {
                $query->where('competition_id', $competition->id);
            })
            ->exists();
    }

    public function hasUnlockedResultHeats(Competition $competition): bool
    {
        return Heat::query()
            ->whereHas('event', fn ($query) => $query->where('competition_id', $competition->id))
            ->whereHas('lanes', fn ($query) => $query->whereNotNull('registration_id'))
            ->whereNull('results_locked_at')
            ->exists();
    }
}
