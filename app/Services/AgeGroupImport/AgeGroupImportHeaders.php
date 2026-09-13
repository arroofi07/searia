<?php

namespace App\Services\AgeGroupImport;

class AgeGroupImportHeaders
{
    public const KODE = 'KODE';

    public const NAMA = 'NAMA';

    public const LABEL = 'LABEL CETAK';

    public const TAHUN_AWAL = 'TAHUN LAHIR AWAL';

    public const TAHUN_AKHIR = 'TAHUN LAHIR AKHIR';

    public const URUTAN = 'URUTAN';

    /**
     * @return list<string>
     */
    public static function required(): array
    {
        return [
            self::KODE,
            self::NAMA,
            self::TAHUN_AWAL,
            self::TAHUN_AKHIR,
        ];
    }

    public static function normalize(string $header): string
    {
        $value = mb_strtoupper(trim($header));
        $value = str_replace(['\\', '_', '-'], ['/', ' ', ' '], $value);
        $collapsed = preg_replace('/\s+/', ' ', $value);
        $value = is_string($collapsed) ? $collapsed : $value;

        return match ($value) {
            'CODE', 'KODE GRUP', 'KODE KELOMPOK' => self::KODE,
            'NAME', 'NAMA GRUP', 'NAMA KELOMPOK' => self::NAMA,
            'LABEL', 'DISPLAY CODE', 'DISPLAY', 'ROMAWI' => self::LABEL,
            'TAHUN AWAL', 'DARI', 'START', 'BIRTH YEAR START' => self::TAHUN_AWAL,
            'TAHUN AKHIR', 'SAMPAI', 'END', 'BIRTH YEAR END' => self::TAHUN_AKHIR,
            'SORT', 'ORDER', 'SORT ORDER' => self::URUTAN,
            default => $value,
        };
    }
}
