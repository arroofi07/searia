<?php

namespace App\Services\Seeding;

use App\Exceptions\UnsupportedLaneCountException;
use App\Models\Registration;

class LaneAssigner
{
    /**
     * @var array<int, list<int>>
     */
    private const ORDERS = [
        4 => [2, 3, 1, 4],
        5 => [3, 2, 4, 1, 5],
        6 => [3, 4, 2, 5, 1, 6],
        8 => [4, 5, 3, 6, 2, 7, 1, 8],
        10 => [5, 6, 4, 7, 3, 8, 2, 9, 1, 10],
    ];

    /**
     * @param  list<Registration>  $entrants  fastest first within the heat
     * @return array<int, Registration> keyed by lane number
     */
    public function assign(array $entrants, int $laneCount): array
    {
        $order = $this->orderFor($laneCount);
        $needed = array_slice($order, 0, count($entrants));
        $assignment = [];

        foreach ($needed as $index => $laneNumber) {
            $assignment[$laneNumber] = $entrants[$index];
        }

        ksort($assignment);

        return $assignment;
    }

    /**
     * @return list<int>
     */
    public function orderFor(int $laneCount): array
    {
        if (! array_key_exists($laneCount, self::ORDERS)) {
            throw new UnsupportedLaneCountException(
                "Jumlah lintasan {$laneCount} tidak didukung. Gunakan 4, 5, 6, 8, atau 10.",
            );
        }

        return self::ORDERS[$laneCount];
    }
}
