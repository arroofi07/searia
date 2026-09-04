<?php

namespace App\Casts;

use App\Support\SwimTime;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<int|null, int|string|SwimTime|null>
 */
class SwimTimeCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return $value === null ? null : (int) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof SwimTime) {
            return $value->milliseconds;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value) && ! str_contains((string) $value, ':') && ! str_contains((string) $value, '.')) {
            return (int) $value;
        }

        return SwimTime::parse((string) $value)?->milliseconds;
    }
}
