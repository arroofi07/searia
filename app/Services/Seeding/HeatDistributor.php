<?php

namespace App\Services\Seeding;

use App\Enums\SeedingMode;
use App\Models\Registration;
use Illuminate\Support\Collection;

class HeatDistributor
{
    /**
     * @param  Collection<int, Registration>  $sortedEntrants  fastest first
     * @return list<list<Registration>> heats indexed 0 = heat 1 (slowest), last = fastest
     */
    public function distribute(Collection $sortedEntrants, int $laneCount, SeedingMode $mode): array
    {
        $count = $sortedEntrants->count();

        if ($count === 0 || $laneCount < 1) {
            return [];
        }

        $heatCount = (int) ceil($count / $laneCount);
        $sizes = match ($mode) {
            SeedingMode::Balanced => $this->balancedSizes($count, $heatCount),
            SeedingMode::FillFromLast => $this->fillFromLastSizes($count, $heatCount, $laneCount),
        };

        $heats = array_fill(0, $heatCount, []);
        $cursor = 0;

        for ($heatIndex = $heatCount - 1; $heatIndex >= 0; $heatIndex--) {
            $size = $sizes[$heatIndex];
            $heats[$heatIndex] = $sortedEntrants->slice($cursor, $size)->values()->all();
            $cursor += $size;
        }

        return $heats;
    }

    /**
     * @return list<int>
     */
    private function balancedSizes(int $count, int $heatCount): array
    {
        $base = intdiv($count, $heatCount);
        $remainder = $count - ($base * $heatCount);
        $sizes = array_fill(0, $heatCount, $base);

        for ($i = $heatCount - 1; $remainder > 0; $i--, $remainder--) {
            $sizes[$i]++;
        }

        return $sizes;
    }

    /**
     * @return list<int>
     */
    private function fillFromLastSizes(int $count, int $heatCount, int $laneCount): array
    {
        $sizes = array_fill(0, $heatCount, 0);
        $remaining = $count;

        for ($i = $heatCount - 1; $i >= 0; $i--) {
            $take = min($laneCount, $remaining);
            $sizes[$i] = $take;
            $remaining -= $take;
        }

        return $sizes;
    }
}
