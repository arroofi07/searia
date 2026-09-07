<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case WaitingVerification = 'waiting_verification';
    case Paid = 'paid';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum dibayar',
            self::WaitingVerification => 'Menunggu verifikasi',
            self::Paid => 'Lunas',
            self::Rejected => 'Ditolak',
        };
    }

    public function canReissue(): bool
    {
        return $this !== self::Paid;
    }

    public function canUploadProof(): bool
    {
        return $this !== self::Paid;
    }
}
