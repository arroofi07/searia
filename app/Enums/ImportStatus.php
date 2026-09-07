<?php

namespace App\Enums;

enum ImportStatus: string
{
    case Uploaded = 'uploaded';
    case Validating = 'validating';
    case Validated = 'validated';
    case Committed = 'committed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Uploaded => 'Diunggah',
            self::Validating => 'Sedang divalidasi',
            self::Validated => 'Siap diimpor',
            self::Committed => 'Tersimpan',
            self::Cancelled => 'Dibatalkan',
            self::Failed => 'Gagal',
        };
    }

    public function isPreviewable(): bool
    {
        return match ($this) {
            self::Validated, self::Failed => true,
            self::Uploaded, self::Validating, self::Committed, self::Cancelled => false,
        };
    }
}
