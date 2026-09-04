<?php

namespace App\Enums;

enum ClubType: string
{
    case Perkumpulan = 'perkumpulan';
    case Sekolah = 'sekolah';

    public function label(): string
    {
        return match ($this) {
            self::Perkumpulan => 'Perkumpulan',
            self::Sekolah => 'Sekolah',
        };
    }
}
