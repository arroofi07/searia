<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Pending => 'Menunggu verifikasi',
            self::Verified => 'Terverifikasi',
            self::Rejected => 'Ditolak',
            self::Withdrawn => 'Dibatalkan',
        };
    }

    public function countsTowardQuota(): bool
    {
        return match ($this) {
            self::Draft, self::Pending, self::Verified, self::Rejected => true,
            self::Withdrawn => false,
        };
    }
}
