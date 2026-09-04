<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Panitia = 'panitia';
    case Pelatih = 'pelatih';
    case Juri = 'juri';
    case Peserta = 'peserta';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Panitia => 'Panitia',
            self::Pelatih => 'Pelatih',
            self::Juri => 'Juri',
            self::Peserta => 'Peserta',
        };
    }
}
