<?php

namespace App\Support;

class MedalIcon
{
    public static function metal(int $place): ?string
    {
        return match ($place) {
            1 => 'gold',
            2 => 'silver',
            3 => 'bronze',
            default => null,
        };
    }

    public static function label(string $metal): string
    {
        return match ($metal) {
            'gold' => 'Emas',
            'silver' => 'Perak',
            'bronze' => 'Perunggu',
            default => '',
        };
    }

    public static function webSrc(string $metal): string
    {
        return asset('images/medals/'.$metal.'.svg');
    }

    /**
     * Kept so stale compiled Blade on cPanel does not call a missing method.
     */
    public static function pdfSrc(string $metal): string
    {
        return self::webSrc($metal);
    }
}
