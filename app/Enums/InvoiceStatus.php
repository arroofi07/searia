<?php

namespace App\Enums;

/**
 * Pendaftar tidak mengunggah bukti transfer sendiri, sehingga tidak ada antrean
 * "menunggu verifikasi": panitia mencocokkan mutasi rekening lalu menandai lunas.
 */
enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum dibayar',
            self::Paid => 'Lunas',
        };
    }

    public function canReissue(): bool
    {
        return $this !== self::Paid;
    }
}
