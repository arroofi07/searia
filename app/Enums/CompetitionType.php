<?php

namespace App\Enums;

enum CompetitionType: string
{
    case Official = 'resmi';
    case Fun = 'fun';

    public function label(): string
    {
        return match ($this) {
            self::Official => 'Resmi',
            self::Fun => 'Fun',
        };
    }
}
