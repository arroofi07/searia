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

    public static function pdfSrc(string $metal): string
    {
        return 'file://'.str_replace('\\', '/', public_path('images/medals/'.$metal.'.svg'));
    }

    public static function webSrc(string $metal): string
    {
        return asset('images/medals/'.$metal.'.svg');
    }
}
