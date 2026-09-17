<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'L';
    case Female = 'P';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Laki-laki',
            self::Female => 'Perempuan',
        };
    }

    public function formLabel(): string
    {
        return match ($this) {
            self::Male => 'L — Laki-laki',
            self::Female => 'P — Perempuan',
        };
    }

    public function eventGender(): EventGender
    {
        return match ($this) {
            self::Male => EventGender::Male,
            self::Female => EventGender::Female,
        };
    }
}
