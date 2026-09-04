<?php

namespace App\Enums;

enum SeedingMode: string
{
    case Balanced = 'balanced';
    case FillFromLast = 'fill_from_last';

    public function label(): string
    {
        return match ($this) {
            self::Balanced => 'Seimbang',
            self::FillFromLast => 'Isi dari seri terakhir',
        };
    }
}
