<?php

namespace App\Enums;

enum EventGender: string
{
    case Male = 'PA';
    case Female = 'PI';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Putra',
            self::Female => 'Putri',
        };
    }

    public function partner(): self
    {
        return match ($this) {
            self::Male => self::Female,
            self::Female => self::Male,
        };
    }
}
