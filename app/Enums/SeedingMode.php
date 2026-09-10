<?php

namespace App\Enums;

enum SeedingMode: string
{
    case Balanced = 'balanced';
    case FillFromLast = 'fill_from_last';

    public function label(): string
    {
        return match ($this) {
            self::Balanced => 'Seimbang',
            self::FillFromLast => 'Isi dari seri terakhir',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Balanced => 'Jumlah perenang per seri dibuat merata. Seri terakhir berisi yang tercepat; seri 1 berisi yang lebih lambat dan yang tanpa catatan waktu (NT).',
            self::FillFromLast => 'Seri terakhir diisi penuh dulu. Sisa perenang masuk ke seri sebelumnya. Cocok jika ingin seri final selalu penuh.',
        };
    }
}
