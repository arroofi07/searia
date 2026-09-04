<?php

namespace App\Enums;

enum Stroke: string
{
    case Butterfly = 'kupu';
    case Backstroke = 'punggung';
    case Breaststroke = 'dada';
    case Freestyle = 'bebas';
    case Medley = 'ganti';

    public function label(): string
    {
        return match ($this) {
            self::Butterfly => 'Gaya Kupu-Kupu',
            self::Backstroke => 'Gaya Punggung',
            self::Breaststroke => 'Gaya Dada',
            self::Freestyle => 'Gaya Bebas',
            self::Medley => 'Gaya Ganti',
        };
    }
}
