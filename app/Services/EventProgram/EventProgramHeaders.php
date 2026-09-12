<?php

namespace App\Services\EventProgram;

class EventProgramHeaders
{
    public const KODE_ACARA = 'KODE ACARA';

    public const NOMOR_PERLOMBAAN = 'NOMOR PERLOMBAAN';

    public const GENDER = 'GENDER';

    public const GRUP = 'GRUP YANG BOLEH IKUT';

    /**
     * @return list<string>
     */
    public static function required(): array
    {
        return [
            self::KODE_ACARA,
            self::NOMOR_PERLOMBAAN,
            self::GENDER,
        ];
    }

    public static function normalize(string $header): string
    {
        $value = mb_strtoupper(trim($header));
        $value = str_replace(['\\', '_', '-'], ['/', ' ', ' '], $value);
        $collapsed = preg_replace('/\s+/', ' ', $value);
        $value = is_string($collapsed) ? $collapsed : $value;

        return match ($value) {
            'KODE', 'ACARA', 'NOMOR ACARA', 'NO' => self::KODE_ACARA,
            'NOMOR LOMBA', 'NAMA', 'NAMA NOMOR', 'PERLOMBAAN' => self::NOMOR_PERLOMBAAN,
            'JENIS KELAMIN', 'PA/PI', 'PA PI' => self::GENDER,
            'GRUP', 'KELOMPOK UMUR', 'KELOMPOK', 'GRUP BOLEH IKUT' => self::GRUP,
            default => $value,
        };
    }
}
