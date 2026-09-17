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
            self::Balanced => 'Jumlah perenang per seri dibuat semerata mungkin. Peserta tercepat masuk seri dengan nomor terbesar (seri terakhir); yang lebih lambat dan NT masuk seri 1.',
            self::FillFromLast => 'Seri dengan nomor terbesar diisi penuh dulu (sampai jumlah lintasan), lalu sisa perenang turun ke seri sebelumnya. Hanya seri 1 yang mungkin tidak penuh.',
        };
    }

    public function example(): string
    {
        return match ($this) {
            self::Balanced => '17 peserta, kolam 6 lintasan → 3 seri berisi 5, 6, dan 6 orang. Seri 3 = yang tercepat; Seri 1 = yang terlambat + NT.',
            self::FillFromLast => '15 peserta, kolam 6 lintasan → 3 seri berisi 3, 6, dan 6 orang. Seri 3 penuh (6 lintasan); Seri 1 yang paling sedikit.',
        };
    }
}
