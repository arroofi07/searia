<?php

namespace App\Enums;

enum CompetitionStatus: string
{
    case Draft = 'draft';
    case Registration = 'registration';
    case Closed = 'closed';
    case Seeded = 'seeded';
    case Running = 'running';
    case Finished = 'finished';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Registration => 'Pendaftaran terbuka',
            self::Closed => 'Pendaftaran ditutup',
            self::Seeded => 'Sudah diseeding',
            self::Running => 'Hari lomba',
            self::Finished => 'Selesai',
            self::Published => 'Dipublikasikan',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedForward(): array
    {
        return match ($this) {
            self::Draft => [self::Registration],
            self::Registration => [self::Closed],
            self::Closed => [self::Seeded],
            self::Seeded => [self::Running],
            self::Running => [self::Finished],
            self::Finished => [self::Published],
            self::Published => [],
        };
    }

    /**
     * @return list<self>
     */
    public function allowedBackward(): array
    {
        return match ($this) {
            self::Draft => [],
            self::Registration => [self::Draft],
            self::Closed => [self::Registration],
            self::Seeded => [self::Closed],
            self::Running => [self::Seeded],
            self::Finished => [self::Running],
            self::Published => [self::Finished],
        };
    }

    public function canMoveTo(self $target): bool
    {
        return in_array($target, $this->allowedForward(), true)
            || in_array($target, $this->allowedBackward(), true);
    }

    public function isBackward(self $target): bool
    {
        return in_array($target, $this->allowedBackward(), true);
    }
}
