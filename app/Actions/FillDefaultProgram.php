<?php

namespace App\Actions;

use App\Enums\EventGender;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Support\Facades\DB;

class FillDefaultProgram
{
    public function handle(Competition $competition): int
    {
        $groups = $competition->ageGroups()->get()->keyBy('code');
        $created = 0;

        DB::transaction(function () use ($competition, $groups, &$created): void {
            foreach (Event::defaultProgram() as $pair) {
                foreach ([
                    ['number' => $pair['male_number'], 'gender' => EventGender::Male],
                    ['number' => $pair['female_number'], 'gender' => EventGender::Female],
                ] as $side) {
                    $event = Event::query()->firstOrNew([
                        'competition_id' => $competition->id,
                        'event_number' => $side['number'],
                    ]);

                    $wasNew = ! $event->exists;
                    $hasRegistrations = ! $wasNew && $event->registrations()->exists();

                    if (! $hasRegistrations) {
                        $event->fill([
                            'gender' => $side['gender'],
                            'distance' => $pair['distance'],
                            'stroke' => $pair['stroke'],
                            'equipment' => $pair['equipment'],
                            'session' => 1,
                            'is_active' => true,
                        ]);
                    }

                    $event->sort_order = $side['number'];
                    $event->save();

                    if ($wasNew) {
                        $created++;
                    }

                    if (! $hasRegistrations) {
                        $codes = Event::defaultEligibleGroupCodes(
                            $pair['distance'],
                            $pair['stroke'],
                            $pair['equipment'],
                        );
                        $event->ageGroups()->sync($groups->only($codes)->pluck('id')->all());
                    }
                }
            }
        });

        return $created;
    }
}
