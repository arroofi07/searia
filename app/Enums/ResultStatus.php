<?php

namespace App\Enums;

enum ResultStatus: string
{
    case Ok = 'ok';
    case Dns = 'dns';
    case Dnf = 'dnf';
    case Dsq = 'dsq';

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'OK',
            self::Dns => 'DNS',
            self::Dnf => 'DNF',
            self::Dsq => 'DSQ',
        };
    }

    public function requiresTime(): bool
    {
        return $this === self::Ok;
    }

    public function requiresDsqCode(): bool
    {
        return $this === self::Dsq;
    }

    /**
     * @return list<self>
     */
    public static function casesOrdered(): array
    {
        return [self::Ok, self::Dns, self::Dnf, self::Dsq];
    }
}
