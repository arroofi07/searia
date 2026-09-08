<?php

namespace App\Services;

use App\Enums\EventGender;
use App\Models\Event;
use Illuminate\Support\Collection;

/**
 * Menyusun baris susunan acara bergaya cetakan resmi: PA | NOMOR | PI.
 */
class ProgramOrderBuilder
{
    /**
     * @param  Collection<int, Event>  $events
     * @return list<array{label: string, pa: ?Event, pi: ?Event, sort: int}>
     */
    public function rows(Collection $events): array
    {
        $groups = $events
            ->sortBy([
                fn (Event $event) => (int) $event->session,
                fn (Event $event) => (int) $event->sort_order,
                fn (Event $event) => (int) $event->event_number,
            ])
            ->groupBy(fn (Event $event): string => implode('|', [
                (int) $event->session,
                (int) $event->distance,
                $event->stroke->value,
                $event->equipment->value,
            ]));

        $rows = [];

        foreach ($groups as $group) {
            /** @var Collection<int, Event> $group */
            $pa = $group->first(fn (Event $event): bool => $event->gender === EventGender::Male);
            $pi = $group->first(fn (Event $event): bool => $event->gender === EventGender::Female);
            $sample = $pa ?? $pi;

            if ($sample === null) {
                continue;
            }

            $rows[] = [
                'label' => $sample->programName(),
                'pa' => $pa,
                'pi' => $pi,
                'sort' => min(
                    $pa?->sort_order ?? PHP_INT_MAX,
                    $pi?->sort_order ?? PHP_INT_MAX,
                    $pa?->event_number ?? PHP_INT_MAX,
                    $pi?->event_number ?? PHP_INT_MAX,
                ),
                'session' => (int) $sample->session,
            ];
        }

        usort($rows, fn (array $a, array $b): int => [$a['session'], $a['sort']] <=> [$b['session'], $b['sort']]);

        return $rows;
    }
}
