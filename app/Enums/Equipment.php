<?php

namespace App\Enums;

enum Equipment: string
{
    case None = 'none';
    case Fins = 'fins';
    case Kickboard = 'kickboard';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Tanpa alat',
            self::Fins => 'Fins',
            self::Kickboard => 'Kickboard',
        };
    }
}
