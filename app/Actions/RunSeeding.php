<?php

namespace App\Actions;

use App\Enums\SeedingMode;
use App\Exceptions\CannotReseedLockedHeatsException;
use App\Models\ActivityLog;
use App\Models\AgeGroup;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Registration;
use App\Models\User;
use App\Services\Seeding\EntrantSorter;
use App\Services\Seeding\HeatDistributor;
use App\Services\Seeding\LaneAssigner;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RunSeeding
{
    public function __construct(
        private readonly EntrantSorter $sorter,
        private readonly HeatDistributor $distributor,
        private readonly LaneAssigner $assigner,
    ) {}

    /**
     * @return Collection<int, Heat>
     */
    public function handle(
        Competition $competition,
        ?Event $event = null,
        ?AgeGroup $ageGroup = null,
        bool $force = false,
        ?User $actor = null,
        ?string $ipAddress = null,
    ): Collection {
        $events = $event !== null
            ? collect([$event])
            : $competition->events()->orderBy('event_number')->get();

        $ageGroups = $ageGroup !== null
            ? collect([$ageGroup])
            : $competition->ageGroups()->orderBy('sort_order')->get();

        return DB::transaction(function () use ($competition, $events, $ageGroups, $force, $actor, $ipAddress): Collection {
            $created = collect();

            foreach ($events as $currentEvent) {
                foreach ($ageGroups as $currentGroup) {
                    $heats = $this->seedPair($competition, $currentEvent, $currentGroup, $force);
                    $created = $created->concat($heats);
                }
            }

            if ($actor !== null && $force) {
                ActivityLog::record(
                    $actor,
                    'seeding.rerun',
                    $competition,
                    null,
                    [
                        'event_ids' => $events->pluck('id')->all(),
                        'age_group_ids' => $ageGroups->pluck('id')->all(),
                        'heat_count' => $created->count(),
                    ],
                    'Seeding ulang paksa',
                    $ipAddress,
                );
            }

            return $created->values();
        });
    }

    /**
     * @return Collection<int, Heat>
     */
    private function seedPair(
        Competition $competition,
        Event $event,
        AgeGroup $ageGroup,
        bool $force,
    ): Collection {
        $existing = Heat::query()
            ->where('event_id', $event->id)
            ->where('age_group_id', $ageGroup->id)
            ->where('round', 'final')
            ->with('lanes')
            ->get();

        if ($existing->contains(fn (Heat $heat): bool => $heat->isLocked()) && ! $force) {
            throw new CannotReseedLockedHeatsException(
                'Seri sudah dikunci. Jalankan ulang hanya dengan penanda paksa.',
            );
        }

        $entrants = Registration::query()
            ->eligibleForSeeding()
            ->where('competition_id', $competition->id)
            ->where('event_id', $event->id)
            ->where('age_group_id', $ageGroup->id)
            ->with(['athlete.club'])
            ->get();

        foreach ($existing as $heat) {
            $heat->lanes()->delete();
            $heat->delete();
        }

        if ($entrants->isEmpty()) {
            return collect();
        }

        $sorted = $this->sorter->sort(
            $entrants,
            $competition->id,
            $event->id,
            $ageGroup->id,
        );

        $mode = $competition->seeding_mode instanceof SeedingMode
            ? $competition->seeding_mode
            : SeedingMode::Balanced;

        $distributed = $this->distributor->distribute(
            $sorted,
            $competition->pool_lanes,
            $mode,
        );

        $heats = collect();

        foreach ($distributed as $index => $heatEntrants) {
            $heat = Heat::query()->create([
                'event_id' => $event->id,
                'age_group_id' => $ageGroup->id,
                'heat_number' => $index + 1,
                'round' => 'final',
                'status' => 'pending',
                'locked_at' => null,
                'seeded_at' => now(),
            ]);

            $lanes = $this->assigner->assign($heatEntrants, $competition->pool_lanes);

            foreach ($lanes as $laneNumber => $registration) {
                HeatLane::query()->create([
                    'heat_id' => $heat->id,
                    'lane_number' => $laneNumber,
                    'registration_id' => $registration->id,
                ]);
            }

            $heats->push($heat->load('lanes'));
        }

        return $heats;
    }
}
