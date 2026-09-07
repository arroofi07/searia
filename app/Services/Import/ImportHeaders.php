<?php

namespace App\Services\Import;

class ImportHeaders
{
    public const NAMA_LENGKAP = 'NAMA LENGKAP';

    public const GENDER = 'L/P';

    public const TAHUN_LAHIR = 'TAHUN LAHIR';

    public const KLUB = 'KLUB/SEKOLAH';

    public const KOTA = 'KABUPATEN/KOTA';

    public const KODE_ACARA = 'KODE ACARA';

    public const CATATAN_WAKTU = 'CATATAN WAKTU';

    public const NO = 'NO';

    /**
     * @return list<string>
     */
    public static function required(): array
    {
        return [
            self::NAMA_LENGKAP,
            self::GENDER,
            self::TAHUN_LAHIR,
            self::KLUB,
            self::KODE_ACARA,
        ];
    }

    public static function normalize(string $header): string
    {
        $value = mb_strtoupper(trim($header));
        $value = str_replace(['\\', '_', '-'], ['/', ' ', ' '], $value);
        $collapsed = preg_replace('/\s+/', ' ', $value);
        $value = is_string($collapsed) ? $collapsed : $value;
        $slashed = preg_replace('/\s*\/\s*/', '/', $value);
        $value = is_string($slashed) ? $slashed : $value;

        return match ($value) {
            'LP', 'JENIS KELAMIN', 'GENDER' => self::GENDER,
            'NAMA' => self::NAMA_LENGKAP,
            'TAHUN', 'THN LAHIR', 'THN' => self::TAHUN_LAHIR,
            'KLUB', 'SEKOLAH', 'KLUB SEKOLAH' => self::KLUB,
            'KOTA', 'KABUPATEN', 'KABUPATEN KOTA' => self::KOTA,
            'ACARA', 'KODE', 'NOMOR ACARA' => self::KODE_ACARA,
            'WAKTU', 'SEED TIME', 'CATATAN' => self::CATATAN_WAKTU,
            default => $value,
        };
    }
}
