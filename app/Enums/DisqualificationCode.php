<?php

namespace App\Enums;

enum DisqualificationCode: string
{
    case Sf = 'SF';
    case St = 'ST';
    case Tn = 'TN';
    case Fn = 'FN';
    case Na = 'NA';
    case Ot = 'OT';

    public function label(): string
    {
        return match ($this) {
            self::Sf => 'SF · Start mendahului aba-aba',
            self::St => 'ST · Gerakan tidak sesuai gaya',
            self::Tn => 'TN · Pembalikan tidak sah',
            self::Fn => 'FN · Sentuhan finis tidak sah',
            self::Na => 'NA · Tidak mencapai dinding',
            self::Ot => 'OT · Lainnya',
        };
    }
}
