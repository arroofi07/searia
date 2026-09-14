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

    public function description(): string
    {
        return match ($this) {
            self::Sf => 'Start mendahului aba-aba',
            self::St => 'Gerakan tidak sesuai gaya',
            self::Tn => 'Pembalikan tidak sah',
            self::Fn => 'Sentuhan finis tidak sah',
            self::Na => 'Tidak mencapai dinding',
            self::Ot => 'Lainnya',
        };
    }

    public function label(): string
    {
        return $this->value.' · '.$this->description();
    }

    public function optionLabel(): string
    {
        return $this->label();
    }
}
